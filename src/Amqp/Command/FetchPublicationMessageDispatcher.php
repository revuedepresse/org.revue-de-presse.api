<?php
declare(strict_types=1);

namespace App\Amqp\Command;

use App\Accessor\Exception\ApiRateLimitingException;
use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\Exception\ReadOnlyApplicationException;
use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Aggregate\Entity\SavedSearch;
use App\Aggregate\Repository\SavedSearchRepository;
use App\Aggregate\Repository\SearchMatchingStatusRepository;
use App\Amqp\Exception\InvalidListNameException;
use App\Amqp\Message\FetchMemberLikes;
use App\Amqp\Message\FetchMemberStatuses;
use App\Api\Entity\Token;
use App\Api\Entity\TokenInterface;
use App\Api\Exception\InvalidSerializedTokenException;
use App\Conversation\Producer\MemberAwareTrait;
use App\Infrastructure\DependencyInjection\MessageBusTrait;
use App\Infrastructure\DependencyInjection\OwnershipAccessorTrait;
use App\Infrastructure\DependencyInjection\TokenChangeTrait;
use App\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Membership\Entity\Member;
use App\Membership\Entity\MemberInterface;
use App\Operation\OperationClock;
use App\Twitter\Api\Resource\OwnershipCollection;
use App\Twitter\Exception\BadAuthenticationDataException;
use App\Twitter\Exception\InconsistentTokenRepository;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\OverCapacityException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use App\Twitter\Exception\UnavailableResourceException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use ReflectionException;
use stdClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use function count;
use function in_array;
use function sprintf;

/**
 * @package App\Amqp\Command
 */
class FetchPublicationMessageDispatcher extends AggregateAwareCommand
{
    private const OPTION_SCREEN_NAME             = 'screen_name';
    private const OPTION_MEMBER_RESTRICTION      = 'member_restriction';
    private const OPTION_INCLUDE_OWNER           = 'include_owner';
    private const OPTION_IGNORE_WHISPERS         = 'ignore_whispers';
    private const MESSAGE_PUBLISHING_NEW_MESSAGE = '[publishing new message produced for "%s"]';

    use MemberAwareTrait;
    use MessageBusTrait;
    use OwnershipAccessorTrait;
    use TranslatorTrait;
    use TokenChangeTrait;

    /**
     * @var OperationClock
     */
    public OperationClock $operationClock;

    /**
     * @var SavedSearchRepository
     */
    public SavedSearchRepository $savedSearchRepository;

    /**
     * @var SearchMatchingStatusRepository
     */
    public SearchMatchingStatusRepository $searchMatchingStatusRepository;

    /**
     * @var bool
     */
    public bool $shouldIncludeOwner = false;

    /**
     * @var MessageBusInterface|null
     */
    private ?MessageBusInterface $likesMessagesProducer = null;

    /**
     * @var string
     */
    private ?string $listRestriction = null;

    /**
     * @var string
     */
    private ?string $memberRestriction = null;

    private ?string $queryRestriction = null;

    /**
     * @var array[string]
     */
    private array $listCollectionRestriction = [];

    /**
     * @var string
     */
    private string $screenName;

    /**
     * @var bool
     */
    private bool $givePriorityToAggregate = false;

    /**
     * @var string
     */
    private ?string $before = null;

    /**
     * @var bool
     */
    private bool $ignoreWhispers = false;

    public function configure()
    {
        $this->setName('press-review:dispatch-messages-to-fetch-member-statuses')
             ->setDescription('Dispatch messages to fetch member statuses')
             ->addOption(
                 'oauth_token',
                 null,
                 InputOption::VALUE_OPTIONAL,
                 'A token is required'
             )->addOption(
                'oauth_secret',
                null,
                InputOption::VALUE_OPTIONAL,
                'A secret is required'
            )->addOption(
                self::OPTION_SCREEN_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'The screen name of a user'
            )->addOption(
                'list',
                null,
                InputOption::VALUE_OPTIONAL,
                'A list to which production is restricted to'
            )->addOption(
                'lists',
                'l',
                InputOption::VALUE_OPTIONAL,
                'List to which publication of messages is restricted to'
            )
             ->addOption(
                 'priority_to_aggregates',
                 'pa',
                 InputOption::VALUE_NONE,
                 'Publish messages the priority queue for visible aggregates'
             )
             ->addOption(
                 'query_restriction',
                 'qr',
                 InputOption::VALUE_OPTIONAL,
                 'Query to search statuses against'
             )->addOption(
                self::OPTION_MEMBER_RESTRICTION,
                'mr',
                InputOption::VALUE_OPTIONAL,
                'Restrict to member, which screen name has been passed as value of this option'
            )->addOption(
                'before',
                null,
                InputOption::VALUE_OPTIONAL,
                'Date before which statuses should have been created'
            )->addOption(
                self::OPTION_INCLUDE_OWNER,
                null,
                InputOption::VALUE_NONE,
                'Should add owner to the list of accounts to be considered'
            )->addOption(
                self::OPTION_IGNORE_WHISPERS,
                'iw',
                InputOption::VALUE_NONE,
                'Should ignore whispers (publication from members having not published anything for a month)'
            )->setAliases(['pr:d-m-t-f-m-s']);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws InvalidSerializedTokenException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;

        $this->setOptionsFromInput();

        if ($this->shouldSkipOperation()) {
            return self::RETURN_STATUS_SUCCESS;
        }

        try {
            $this->setUpDependencies();
        } catch (InvalidSerializedTokenException $exception) {
            return self::RETURN_STATUS_FAILURE;
        }

        if ($this->applyQueryRestriction()) {
            $this->produceSearchStatusesMessages();

            return self::RETURN_STATUS_SUCCESS;
        }

        return $this->producesListMessages();
    }

    /**
     * @param TokenInterface  $token
     * @param stdClass        $list
     * @param MemberInterface $member
     *
     * @return FetchMemberStatuses
     */
    protected function makeMemberIdentityCard(
        TokenInterface $token,
        stdClass $list,
        MemberInterface $member
    ): FetchMemberStatuses {
        $aggregate = $this->getListAggregateByName(
            $member->getTwitterUsername(),
            $list->name,
            $list->id_str
        );

        return new FetchMemberStatuses(
            $member->getTwitterUsername(),
            $aggregate->getId(),
            $token,
            $this->before
        );
    }

    /**
     * @param stdClass $twitterUser
     * @param stdClass $friend
     * @param bool     $protected
     * @param bool     $suspended
     *
     * @return Member
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function makeUser(
        stdClass $twitterUser,
        stdClass $friend,
        bool $protected = false,
        bool $suspended = false
    ) {
        $this->logger->info(
            sprintf(
                self::MESSAGE_PUBLISHING_NEW_MESSAGE,
                $twitterUser->screen_name
            )
        );

        $user = $this->userRepository->make(
            $friend->id,
            $twitterUser->screen_name,
            $protected,
            $suspended
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * @param $members
     * @param $messageBody
     * @param $list
     *
     * @return int
     * @throws Exception
     */
    protected function publishMembersScreenNames(
        $members,
        TokenInterface $messageBody,
        $list
    ) {
        $publishedMessages = 0;

        foreach ($members->users as $friend) {
            if ($this->memberRestriction && $friend->screen_name !== $this->memberRestriction) {
                $this->output->writeln(
                    sprintf(
                        'Skipping "%s" as member restriction applies',
                        $friend->screen_name
                    )
                );
                continue;
            }

            try {
                $member = $this->getMessageMember($friend);

                if ($this->ignoreWhispers && $member->isAWhisperer()) {
                    $message = sprintf('Ignoring whisperer with screen name "%s"', $friend->screen_name);
                    $this->logger->info($message);

                    continue;
                }

                if ($member->isProtected()) {
                    $message = sprintf('Ignoring protected member with screen name "%s"', $friend->screen_name);
                    $this->logger->info($message);

                    continue;
                }

                if ($member->isSuspended()) {
                    $message = sprintf('Ignoring suspended member with screen name "%s"', $friend->screen_name);
                    $this->logger->info($message);

                    continue;
                }

                $fetchMemberStatuses = $this->makeMemberIdentityCard(
                    $messageBody,
                    $list,
                    $member
                );

                $this->dispatcher->dispatch($fetchMemberStatuses);

                if ($this->likesMessagesProducer instanceof MessageBusInterface) {
                    $this->likesMessagesProducer->dispatch(
                        FetchMemberLikes::from(
                            $fetchMemberStatuses
                        )
                    );
                }

                $publishedMessages++;
            } catch (Exception $exception) {
                if ($this->shouldBreakPublication($exception)) {
                    $this->logger->info($exception->getMessage());

                    break;
                }

                if ($this->shouldContinuePublication($exception)) {
                    continue;
                }

                throw $exception;
            }
        }

        return $publishedMessages;
    }

    /**
     * @return bool
     */
    private function applyQueryRestriction(): bool
    {
        return $this->queryRestriction !== null;
    }

    /**
     * @param OwnershipCollection $ownerships
     *
     * @return OwnershipCollection
     */
    private function findNextBatchOfListOwnerships(OwnershipCollection $ownerships): OwnershipCollection
    {
        $previousCursor = -1;

        if ($this->listRestriction === null) {
            return $this->accessor->getUserOwnerships(
                $this->screenName,
                $ownerships->nextPage()
            );
        }

        while ($this->targetListHasNotBeenFound($ownerships, $this->listRestriction)) {
            $ownerships = $this->accessor->getUserOwnerships(
                $this->screenName,
                $ownerships->nextPage()
            );

            if (!$ownerships->nextPage() || $previousCursor === $ownerships->nextPage()) {
                $this->output->write(
                    sprintf(
                        implode(
                            [
                                'No more pages of members lists to be processed. ',
                                'Does the Twitter API access token used belong to "%s"?',
                            ]
                        ),
                        $this->screenName
                    )
                );

                break;
            }

            $previousCursor = $ownerships->nextPage();
        }

        return $ownerships;
    }

    /**
     * @param OwnershipCollection $ownerships
     *
     * @return OwnershipCollection
     * @throws InvalidListNameException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    private function guardAgainstInvalidListName(OwnershipCollection $ownerships): OwnershipCollection
    {
        if ($this->listRestriction === null) {
            return $ownerships;
        }

        if ($this->targetListHasNotBeenFound($ownerships, $this->listRestriction)) {
            $ownerships = $this->guardAgainstInvalidToken($ownerships);
        }

        if ($this->targetListHasNotBeenFound($ownerships, $this->listRestriction)) {
            $message = sprintf(
                'Invalid list name ("%s"). Could not be found',
                $this->listRestriction
            );
            $this->output->writeln($message);

            throw new InvalidListNameException($message);
        }

        return $ownerships;
    }

    /**
     * @param $ownerships
     *
     * @return \API|mixed|object|\stdClass
     * @throws UnavailableResourceException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws Exception
     * @throws SuspendedAccountException
     */
    private function guardAgainstInvalidToken(OwnershipCollection $ownerships): OwnershipCollection
    {
        $this->tokenChange->replaceAccessToken(
            Token::fromArray($this->getTokensFromInputOrFallback()),
            $this->accessor
        );
        $ownerships->goBackToFirstPage();

        return $this->findNextBatchOfListOwnerships($ownerships);
    }

    /**
     * @param $ownerships
     *
     * @return array
     */
    private function mapOwnershipsLists(OwnershipCollection $ownerships): array
    {
        return array_map(
            fn($list) => $list->name,
            $ownerships->toArray()
        );
    }

    /**
     * @param                $doNotApplyListRestriction
     * @param                $list
     * @param TokenInterface $messageBody
     *
     * @return void
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws InvalidSerializedTokenException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    private function processMemberList(
        $doNotApplyListRestriction,
        $list,
        TokenInterface $messageBody
    ) {
        if (
            $doNotApplyListRestriction || $list->name === $this->listRestriction
            || array_key_exists(
                $list->name,
                $this->listCollectionRestriction
            )
        ) {
            $members = $this->accessor->getListMembers($list->id);

            if ($this->shouldIncludeOwner) {
                $additionalMember = $this->accessor->getMemberProfile($this->screenName);
                array_unshift($members->users, $additionalMember);
                $this->shouldIncludeOwner = false;
            }

            if (!is_object($members) || !isset($members->users) || count($members->users) === 0) {
                $this->logger->info(sprintf('List "%s" has no members', $list->name));
            }

            if (count($members->users) > 0) {
                $this->logger->info(
                    sprintf(
                        'About to publish messages for members in list "%s"',
                        $list->name
                    )
                );
            }

            $publishedMessages = $this->publishMembersScreenNames(
                $members,
                $messageBody,
                $list
            );

            // Reset accessor tokens in case they've been updated to find member usernames with alternative tokens
            // Members lists can only be accessed by authenticated users owning the lists
            // See also https://dev.twitter.com/rest/reference/get/lists/ownerships
            $this->tokenChange->replaceAccessToken(
                Token::fromArray($this->getTokensFromInputOrFallback()),
                $this->accessor
            );

            $this->output->writeln(
                $this->translator->trans(
                    'amqp.production.list_members.success',
                    [
                        '{{ count }}' => $publishedMessages,
                        '{{ list }}'  => $list->name,
                    ]
                )
            );
        }
    }

    /**
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    private function produceSearchStatusesMessages(): void
    {
        $savedSearch = $this->savedSearchRepository
            ->findOneBy(['searchQuery' => $this->queryRestriction]);

        if (!($savedSearch instanceof SavedSearch)) {
            $response    = $this->accessor->saveSearch($this->queryRestriction);
            $savedSearch = $this->savedSearchRepository->make($response);
            $this->savedSearchRepository->save($savedSearch);
        }

        $results = $this->accessor->search($savedSearch->searchQuery);

        $this->searchMatchingStatusRepository->saveSearchMatchingStatus(
            $savedSearch,
            $results->statuses,
            $this->accessor->userToken
        );
    }

    /**
     * @return int
     * @throws InvalidSerializedTokenException
     */
    private function producesListMessages(): int
    {
        try {
            $memberOwnership = $this->ownershipAccessor->getOwnershipsForMemberHavingScreenNameAndToken(
                $this->screenName,
                Token::fromArray($this->getTokensFromInputOrFallback())
            );
        } catch (OverCapacityException $exception) {
            $this->output->writeln($exception->getMessage());

            return self::RETURN_STATUS_SUCCESS;
        }

        $this->shouldIncludeOwner = $this->shouldIncludeOwner();

        foreach ($memberOwnership->ownershipCollection()->toArray() as $list) {
            try {
                $this->processMemberList(
                    $this->shouldApplyListRestriction(),
                    $list,
                    $memberOwnership->token()
                );
            } catch (Exception $exception) {
                $this->logger->critical($exception->getMessage());

                return self::RETURN_STATUS_FAILURE;
            }
        }

        return self::RETURN_STATUS_SUCCESS;
    }

    private function setOptionsFromInput(): void
    {
        $this->screenName = $this->input->getOption(self::OPTION_SCREEN_NAME);

        $this->listRestriction = null;
        if ($this->input->hasOption('list') && !is_null($this->input->getOption('list'))) {
            $this->listRestriction = $this->input->getOption('list');
        }

        $this->listCollectionRestriction = [];
        if ($this->input->hasOption('lists') && !is_null($this->input->getOption('lists'))) {
            $this->listCollectionRestriction = explode(',', $this->input->getOption('lists'));
            $restiction                      = (object) [];
            $restiction->list                = [];
            array_walk(
                $this->listCollectionRestriction,
                function ($list) use ($restiction) {
                    $restiction->list[$list] = $list;
                }
            );
            $this->listCollectionRestriction = $restiction->list;
        }

        if ($this->input->hasOption('before') && !is_null($this->input->getOption('before'))) {
            $this->before = $this->input->getOption('before');
        }

        if ($this->input->hasOption('priority_to_aggregates') && $this->input->getOption('priority_to_aggregates')) {
            $this->givePriorityToAggregate = true;
        }

        if ($this->input->hasOption('query_restriction') && $this->input->getOption('query_restriction')) {
            $this->queryRestriction = $this->input->getOption('query_restriction');
        }

        if (
            $this->input->hasOption(self::OPTION_MEMBER_RESTRICTION)
            && $this->input->getOption(
                self::OPTION_MEMBER_RESTRICTION
            )
        ) {
            $this->memberRestriction = $this->input->getOption(self::OPTION_MEMBER_RESTRICTION);
        }

        if (
            $this->input->hasOption(self::OPTION_IGNORE_WHISPERS)
            && $this->input->getOption(
                self::OPTION_IGNORE_WHISPERS
            )
        ) {
            $this->ignoreWhispers = $this->input->getOption(self::OPTION_IGNORE_WHISPERS);
        }
    }

    /**
     * @throws InvalidSerializedTokenException
     */
    private function setUpDependencies(): void
    {
        $this->setUpLogger();

        $tokens = $this->getTokensFromInputOrFallback();
        $this->setOAuthTokens(Token::fromArray($tokens));

        $this->setupAggregateRepository();

        if (
            ($this->listRestriction !== null || $this->listCollectionRestriction !== null)
            && $this->givePriorityToAggregate
        ) {
            $this->dispatcher = $this->getContainer()
                                     ->get(
                                         'old_sound_rabbit_mq.weaving_the_web_amqp.twitter.aggregates_status_producer'
                                     );

            $this->likesMessagesProducer = $this->getContainer()
                                                ->get(
                                                    'old_sound_rabbit_mq.weaving_the_web_amqp.producer.aggregates_likes_producer'
                                                );
        }

        if ($this->applyQueryRestriction()) {
            $this->dispatcher = $this->getContainer()
                                     ->get(
                                         'old_sound_rabbit_mq.weaving_the_web_amqp.producer.search_matching_statuses_producer'
                                     );
        }
    }

    /**
     * @return bool
     */
    private function shouldApplyListRestriction(): bool
    {
        return $this->listRestriction === null
            && count($this->listCollectionRestriction) === 0;
    }

    /**
     * @return bool
     */
    private function shouldIncludeOwner(): bool
    {
        return $this->input->hasOption(self::OPTION_INCLUDE_OWNER)
            && $this->input->getOption(self::OPTION_INCLUDE_OWNER);
    }

    /**
     * @return bool
     */
    private function shouldSkipOperation(): bool
    {
        return $this->operationClock->shouldSkipOperation()
            && !$this->givePriorityToAggregate
            && !$this->queryRestriction;
    }

    /**
     * @param $ownerships
     * @param $listRestriction
     *
     * @return bool
     */
    private function targetListHasBeenFound($ownerships, string $listRestriction): bool
    {
        $listNames = $this->mapOwnershipsLists($ownerships);

        return in_array($listRestriction, $listNames, true);
    }

    /**
     * @param        $ownerships
     * @param string $listRestriction
     *
     * @return bool
     */
    private function targetListHasNotBeenFound($ownerships, string $listRestriction): bool
    {
        return !$this->targetListHasBeenFound($ownerships, $listRestriction);
    }
}
