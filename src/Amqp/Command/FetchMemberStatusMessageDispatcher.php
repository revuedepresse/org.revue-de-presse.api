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
use App\Amqp\Message\FetchMemberLikes;
use App\Amqp\Message\FetchMemberStatuses;
use App\Api\Entity\NullToken;
use App\Api\Entity\TokenInterface;
use App\Api\Exception\InvalidTokenException;
use App\Api\Exception\UnavailableTokenException;
use App\Conversation\Producer\MemberAwareTrait;
use App\Membership\Entity\Member;
use App\Membership\Entity\MemberInterface;
use App\Operation\OperationClock;

use App\Twitter\Exception\BadAuthenticationDataException;
use App\Twitter\Exception\InconsistentTokenRepository;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use ReflectionException;
use stdClass;
use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

use App\Amqp\Exception\InvalidListNameException;

use App\Api\Entity\Token;

use App\Twitter\Exception\UnavailableResourceException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function in_array;
use function sprintf;

/**
 * @package App\Amqp\Command
 */
class FetchMemberStatusMessageDispatcher extends AggregateAwareCommand
{
    private const OPTION_SCREEN_NAME = 'screen_name';

    private const OPTION_MEMBER_RESTRICTION = 'member_restriction';

    private const OPTION_INCLUDE_OWNER = 'include_owner';

    private const OPTION_IGNORE_WHISPERS = 'ignore_whispers';

    private const MESSAGE_PUBLISHING_NEW_MESSAGE = '[publishing new message produced for "%s"]';

    use MemberAwareTrait;

    /**
     * @var MessageBusInterface
     */
    private MessageBusInterface $producer;

    /**
     * @var MessageBusInterface|null
     */
    private ?MessageBusInterface $likesMessagesProducer = null;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @param TranslatorInterface $translator
     *
     * @return $this
     */
    public function setTranslator(TranslatorInterface $translator): self
    {
        $this->translator = $translator;

        return $this;
    }

    /**
     * @var string
     */
    private ?string $listRestriction = null;

    /**
     * @var string
     */
    private ?string $memberRestriction = null;

    /**
     * @var string
     */
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
     * @var bool
     */
    private bool $ignoreWhispers = false;

    /**
     * @param $messageBus
     *
     * @return $this
     */
    public function setMessageBus(MessageBusInterface $messageBus): self
    {
        $this->producer = $messageBus;

        return $this;
    }

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
     * @throws InvalidListNameException
     * @throws InvalidTokenException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->setOptionsFromInput();

        if ($this->operationClock->shouldSkipOperation()
            && !$this->givePriorityToAggregate
            && !$this->queryRestriction
        ) {
            return self::RETURN_STATUS_SUCCESS;
        }

        $this->setUpDependencies();

        if ($this->applyQueryRestriction()) {
            $this->productSearchStatusesMessages();

            return self::RETURN_STATUS_SUCCESS;
        }

        return $this->producesListsMessages();
    }

    /**
     * @param array           $messageBody
     * @param stdClass        $list
     * @param MemberInterface $member
     *
     * @return FetchMemberStatuses
     */
    protected function makeMemberIdentityCard(
        array $messageBody,
        stdClass $list,
        MemberInterface $member
    ): FetchMemberStatuses {
        $credentials = $messageBody;

        $aggregate = $this->getListAggregateByName(
            $member->getTwitterUsername(),
            $list->name,
            $list->id_str
        );

        return new FetchMemberStatuses(
            $member->getTwitterUsername(),
            $aggregate->getId(),
            $credentials,
            $this->before
        );
    }

    /**
     * @param $members
     * @param $messageBody
     * @param $list
     *
     * @return int
     * @throws Exception
     */
    protected function publishMembersScreenNames($members, $messageBody, $list)
    {
        $publishedMessages = 0;

        foreach ($members->users as $friend) {
            if ($this->memberRestriction && $friend->screen_name !== $this->memberRestriction) {
                $this->output->writeln(sprintf(
                    'Skipping "%s" as member restriction applies',
                    $friend->screen_name
                ));
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

                $this->producer->dispatch($fetchMemberStatuses);

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
    )
    {
        $this->logger->info(sprintf(
            self::MESSAGE_PUBLISHING_NEW_MESSAGE,
            $twitterUser->screen_name
        ));

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
     * @return bool
     */
    private function applyQueryRestriction(): bool
    {
        if ($this->queryRestriction === null) {
            return false;
        }

        return $this->queryRestriction;
    }

    /**
     * @param $ownerships
     * @param $listRestriction
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
     * @return bool
     */
    private function targetListHasNotBeenFound($ownerships, string $listRestriction): bool {
        return ! $this->targetListHasBeenFound($ownerships, $listRestriction);
    }

    /**
     * @param $ownerships
     * @return array
     */
    private function mapOwnershipsLists($ownerships): array
    {
        return array_map(function ($list) {
            $list = (array) $list;

            return $list['name'];
        }, (array)$ownerships->lists);
    }

    /**
     * @param stdClass $ownerships
     *
     * @return stdClass
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     * @throws NotFoundStatusException
     * @throws NotFoundMemberException
     * @throws ProtectedAccountException
     */
    private function findNextBatchOfListOwnerships(stdClass $ownerships): stdClass
    {
        $previousCursor = -1;

        if ($this->listRestriction === null) {
            return $this->accessor->getUserOwnerships(
                $this->screenName,
                $ownerships->next_cursor
            );
        }

        while ($this->targetListHasNotBeenFound($ownerships, $this->listRestriction)) {
            $ownerships = $this->accessor->getUserOwnerships($this->screenName, $ownerships->next_cursor);

            if (!isset($ownerships->next_cursor) || $previousCursor === $ownerships->next_cursor) {
                $this->output->write(sprintf(implode([
                    'No more pages of members lists to be processed. ',
                    'Does the Twitter API access token used belong to "%s"?',
                ]), $this->screenName));

                break;
            }

            $previousCursor = $ownerships->next_cursor;
        }

        return $ownerships;
    }

    private function setUpDependencies()
    {
        $tokens = $this->getTokensFromInput();

        $this->setOAuthTokens($tokens);

        $this->setupAggregateRepository();
        $this->setUpLogger();

        if (($this->listRestriction !== null || $this->listCollectionRestriction !== null) &&
            $this->givePriorityToAggregate
        ) {
            $this->producer = $this->getContainer()
                ->get('old_sound_rabbit_mq.weaving_the_web_amqp.twitter.aggregates_status_producer');

            $this->likesMessagesProducer = $this->getContainer()
                ->get('old_sound_rabbit_mq.weaving_the_web_amqp.producer.aggregates_likes_producer');
        }

        if ($this->applyQueryRestriction()) {
            $this->producer = $this->getContainer()
                ->get('old_sound_rabbit_mq.weaving_the_web_amqp.producer.search_matching_statuses_producer');
        }
    }

    /**
     * @return array
     */
    private function updateAccessToken(): array
    {
        $tokens = $this->getTokensFromInput();

        $token = new NullToken;

        try {
            /** @var Token $token */
            $token = $this->findTokenOtherThan($tokens['token']);
        } catch (NoResultException|NonUniqueResultException $exception) {
            $this->logger->error($exception->getMessage());
        }

        if (!($token instanceof TokenInterface) ||
            $token instanceof NullToken
        ) {
            UnavailableTokenException::throws();
        }

        $oauthTokens = [
            'token' => $token->getOauthToken(),
            'secret' => $token->getOauthTokenSecret(),
            'consumer_token' => $token->consumerKey,
            'consumer_secret' => $token->consumerSecret
        ];
        $this->setOAuthTokens($oauthTokens);

        return $oauthTokens;
    }

    /**
     * @param $ownerships
     * @return \API|mixed|object|\stdClass
     * @throws InvalidListNameException
     * @throws UnavailableResourceException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws Exception
     * @throws InvalidTokenException
     * @throws SuspendedAccountException
     */
    private function guardAgainstInvalidListName($ownerships)
    {
        if ($this->listRestriction) {
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
        }

        return $ownerships;
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
            $restiction = (object)[];
            $restiction->list = [];
            array_walk(
                $this->listCollectionRestriction,
                function($list) use ($restiction) {
                    $restiction->list[$list] = $list;
                }
            );
            $this->listCollectionRestriction = $restiction->list;
        }

        if ($this->input->hasOption('before') && !is_null($this->input->getOption('before'))) {
            $this->before = $this->input->getOption('before');
        }

        if ($this->input->hasOption('priority_to_aggregates') &&
            $this->input->getOption('priority_to_aggregates')) {
            $this->givePriorityToAggregate = true;
        }

        if ($this->input->hasOption('query_restriction') &&
            $this->input->getOption('query_restriction')) {
            $this->queryRestriction = $this->input->getOption('query_restriction');
        }

        if ($this->input->hasOption(self::OPTION_MEMBER_RESTRICTION) &&
            $this->input->getOption(self::OPTION_MEMBER_RESTRICTION)) {
            $this->memberRestriction = $this->input->getOption(self::OPTION_MEMBER_RESTRICTION);
        }

        if ($this->input->hasOption(self::OPTION_IGNORE_WHISPERS) &&
            $this->input->getOption(self::OPTION_IGNORE_WHISPERS)) {
            $this->ignoreWhispers = $this->input->getOption(self::OPTION_IGNORE_WHISPERS);
        }
    }

    /**
     * @param $ownerships
     * @return \API|mixed|object|\stdClass
     * @throws UnavailableResourceException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws Exception
     * @throws InvalidTokenException
     * @throws SuspendedAccountException
     */
    private function guardAgainstInvalidToken($ownerships)
    {
        $this->updateAccessToken();
        $ownerships->next_cursor = -1;

        return $this->findNextBatchOfListOwnerships($ownerships);
    }

    /**
     * @return int
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws InvalidListNameException
     * @throws InvalidTokenException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    private function producesListsMessages(): int
    {
        $unfrozenTokenCount = $this->tokenRepository->howManyUnfrozenTokenAreThere();

        while ($unfrozenTokenCount > 0) {
            try {
                $ownerships = $this->accessor->getUserOwnerships($this->screenName);

                if (count($ownerships->lists) === 0) {
                    continue;
                }

                $messageBody = $this->getTokensFromInput();

                break;
            } catch (UnavailableResourceException $exception) {
                $this->logger->info($exception->getMessage());
                $messageBody = $this->updateAccessToken();
            }

            $unfrozenTokenCount--;
        }

        if (!isset($ownerships)) {
            $this->output->writeln('Over capacity usage of all available tokens.');

            return self::RETURN_STATUS_SUCCESS;
        }

        $ownerships = $this->guardAgainstInvalidListName($ownerships);

        $doNotApplyListRestriction = is_null($this->listRestriction) &&
            count($this->listCollectionRestriction) === 0;
        if ($doNotApplyListRestriction && count($ownerships->lists) === 0) {
            $ownerships = $this->guardAgainstInvalidToken($ownerships);
        }

        $this->shouldIncludeOwner = $this->input->hasOption(self::OPTION_INCLUDE_OWNER) &&
            $this->input->getOption(self::OPTION_INCLUDE_OWNER);
        $shouldIncludeOwner = $this->shouldIncludeOwner;

        foreach ($ownerships->lists as $list) {
            try {
                $shouldIncludeOwner = $this->processMemberList(
                    $doNotApplyListRestriction,
                    $list,
                    $shouldIncludeOwner,
                    $messageBody
                );
            } catch (Exception $exception) {
                $this->logger->critical($exception->getMessage());

                return self::RETURN_STATUS_FAILURE;
            }
        }

        return self::RETURN_STATUS_SUCCESS;
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
    private function productSearchStatusesMessages()
    {
        $savedSearch = $this->savedSearchRepository
            ->findOneBy(['searchQuery' => $this->queryRestriction]);

        if (!($savedSearch instanceof SavedSearch)) {
            $response = $this->accessor->saveSearch($this->queryRestriction);
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
     * @param $doNotApplyListRestriction
     * @param $list
     * @param $shouldIncludeOwner
     * @param $messageBody
     *
     * @return bool
     * @throws ApiRateLimitingException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    private function processMemberList(
        $doNotApplyListRestriction,
        $list,
        $shouldIncludeOwner,
        $messageBody
    ) {
        if ($doNotApplyListRestriction ||
            $list->name === $this->listRestriction ||
            array_key_exists($list->name, $this->listCollectionRestriction)
        ) {
            $members = $this->accessor->getListMembers($list->id);

            if ($shouldIncludeOwner) {
                $additionalMember = $this->accessor->showUser($this->screenName);
                array_unshift($members->users, $additionalMember);
                $shouldIncludeOwner = false;
            }

            if (!is_object($members) || !isset($members->users) || count($members->users) === 0) {
                $this->logger->info(sprintf('List "%s" has no members', $list->name));

                return $shouldIncludeOwner;
            }

            if (count($members->users) > 0) {
                $this->logger->info(
                    sprintf(
                        'About to publish messages for members in list "%s"',
                        $list->name
                    )
                );
            }

            $publishedMessages = $this->publishMembersScreenNames($members, $messageBody, $list);

            // Reset accessor tokens in case they've been updated to find member usernames with alternative tokens
            // Members lists can only be accessed by authenticated users owning the lists
            // See also https://dev.twitter.com/rest/reference/get/lists/ownerships
            $this->updateAccessToken();

            $this->output->writeln($this->translator->trans('amqp.production.list_members.success', [
                '{{ count }}' => $publishedMessages,
                '{{ list }}' => $list->name,
            ]));
        }

        return $shouldIncludeOwner;
    }
}
