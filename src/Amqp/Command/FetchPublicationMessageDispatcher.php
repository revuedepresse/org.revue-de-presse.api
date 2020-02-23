<?php
declare(strict_types=1);

namespace App\Amqp\Command;

use App\Aggregate\Entity\SavedSearch;
use App\Aggregate\Repository\SavedSearchRepository;
use App\Aggregate\Repository\SearchMatchingStatusRepository;
use App\Amqp\Exception\InvalidListNameException;
use App\Infrastructure\Amqp\Message\FetchMemberLikes;
use App\Infrastructure\Amqp\Message\FetchMemberStatuses;
use App\Amqp\SkippableMemberException;
use App\Api\Entity\Token;
use App\Api\Entity\TokenInterface;
use App\Api\Exception\InvalidSerializedTokenException;
use App\Conversation\Producer\MemberAwareTrait;
use App\Domain\Membership\MemberFacingStrategy;
use App\Domain\Collection\PublicationCollectionStrategy;
use App\Domain\Collection\PublicationStrategyInterface;
use App\Infrastructure\DependencyInjection\MessageBusTrait;
use App\Infrastructure\DependencyInjection\OwnershipAccessorTrait;
use App\Infrastructure\DependencyInjection\TokenChangeTrait;
use App\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Infrastructure\InputConverter\InputToCollectionStrategy;
use App\Membership\Entity\Member;
use App\Membership\Exception\InvalidMemberIdentifier;
use App\Operation\OperationClock;
use App\Domain\Resource\MemberCollection;
use App\Domain\Resource\MemberIdentity;
use App\Domain\Resource\OwnershipCollection;
use App\Domain\Resource\PublicationList;
use App\Twitter\Exception\EmptyListException;
use App\Twitter\Exception\OverCapacityException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use stdClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use function in_array;
use function sprintf;

/**
 * @package App\Amqp\Command
 */
class FetchPublicationMessageDispatcher extends AggregateAwareCommand implements PublicationStrategyInterface
{
    private const OPTION_BEFORE                 = self::RULE_BEFORE;
    private const OPTION_SCREEN_NAME            = self::RULE_SCREEN_NAME;
    private const OPTION_MEMBER_RESTRICTION     = self::RULE_MEMBER_RESTRICTION;
    private const OPTION_INCLUDE_OWNER          = self::RULE_INCLUDE_OWNER;
    private const OPTION_IGNORE_WHISPERS        = self::RULE_IGNORE_WHISPERS;
    private const OPTION_QUERY_RESTRICTION      = self::RULE_QUERY_RESTRICTION;
    private const OPTION_PRIORITY_TO_AGGREGATES = self::RULE_PRIORITY_TO_AGGREGATES;

    private const OPTION_LIST                    = self::RULE_LIST;
    private const OPTION_LISTS                   = self::RULE_LISTS;
    private const OPTION_OAUTH_TOKEN             = 'oauth_token';
    private const OPTION_OAUTH_SECRET            = 'oauth_secret';
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
     * @var PublicationCollectionStrategy
     */
    private PublicationCollectionStrategy $collectionStrategy;

    public function configure()
    {
        $this->setName('press-review:dispatch-messages-to-fetch-member-statuses')
             ->setDescription('Dispatch messages to fetch member statuses')
             ->addOption(
                 self::OPTION_OAUTH_TOKEN,
                 null,
                 InputOption::VALUE_OPTIONAL,
                 'A token is required'
             )->addOption(
                self::OPTION_OAUTH_SECRET,
                null,
                InputOption::VALUE_OPTIONAL,
                'A secret is required'
            )->addOption(
                self::OPTION_SCREEN_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'The screen name of a user'
            )->addOption(
                self::OPTION_LIST,
                null,
                InputOption::VALUE_OPTIONAL,
                'A list to which production is restricted to'
            )->addOption(
                self::OPTION_LISTS,
                'l',
                InputOption::VALUE_OPTIONAL,
                'List to which publication of messages is restricted to'
            )
             ->addOption(
                 self::OPTION_PRIORITY_TO_AGGREGATES,
                 'pa',
                 InputOption::VALUE_NONE,
                 'Publish messages the priority queue for visible aggregates'
             )
             ->addOption(
                 self::OPTION_QUERY_RESTRICTION,
                 'qr',
                 InputOption::VALUE_OPTIONAL,
                 'Query to search statuses against'
             )->addOption(
                self::OPTION_MEMBER_RESTRICTION,
                'mr',
                InputOption::VALUE_OPTIONAL,
                'Restrict to member, which screen name has been passed as value of this option'
            )->addOption(
                self::OPTION_BEFORE,
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
     * @throws InvalidSerializedTokenException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws InvalidListNameException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;

        $this->collectionStrategy = InputToCollectionStrategy::convertInputToCollectionStrategy($input);

        if ($this->shouldSkipOperation()) {
            return self::RETURN_STATUS_SUCCESS;
        }

        try {
            $this->setUpDependencies();
        } catch (InvalidSerializedTokenException $exception) {
            return self::RETURN_STATUS_FAILURE;
        }

        if ($this->collectionStrategy->shouldSearchByQuery()) {
            $this->produceSearchStatusesMessages();

            return self::RETURN_STATUS_SUCCESS;
        }

        return $this->producesListMessages();
    }

    /**
     * @param MemberCollection $memberCollection
     *
     * @return MemberCollection
     */
    private function addOwnerToListOptionally(MemberCollection $memberCollection): MemberCollection
    {
        $members = $memberCollection->toArray();
        if ($this->collectionStrategy->shouldIncludeOwner()) {
            $additionalMember = $this->accessor->getMemberProfile(
                $this->collectionStrategy->onBehalfOfWhom()
            );
            array_unshift($members, $additionalMember);
            $this->collectionStrategy->willIncludeOwner(false);
        }

        return MemberCollection::fromArray($members);
    }

    /**
     * @param MemberCollection $members
     * @param TokenInterface   $token
     * @param PublicationList  $list
     *
     * @return int
     * @throws Exception
     */
    private function dispatchMessages(
        MemberCollection $members,
        TokenInterface $token,
        PublicationList $list
    ): int {
        $publishedMessages = 0;

        /** @var MemberIdentity $memberIdentity */
        foreach ($members->toArray() as $memberIdentity) {
            try {
                $this->skipUnrestrictedMember($memberIdentity);

                $member = $this->getMessageMember($memberIdentity);

                $this->collectionStrategy->guardAgainstWhisperingMember($member, $memberIdentity);

                MemberFacingStrategy::guardAgainstProtectedMember($member, $memberIdentity);
                MemberFacingStrategy::guardAgainstSuspendedMember($member, $memberIdentity);

                $fetchMemberStatuses = FetchMemberStatuses::makeMemberIdentityCard(
                    $this->getListAggregateByName(
                        $member->getTwitterUsername(),
                        $list->name(),
                        $list->id()
                    ),
                    $token,
                    $member,
                    $this->collectionStrategy->collectPublicationsPrecedingThoseAlreadyCollected()
                );

                $this->dispatcher->dispatch($fetchMemberStatuses);

                $this->dispatcher->dispatch(
                    FetchMemberLikes::from(
                        $fetchMemberStatuses
                    )
                );

                $publishedMessages++;
            } catch (SkippableMemberException $exception) {
                $this->logger->info($exception->getMessage());
            } catch (Exception $exception) {
                if (MemberFacingStrategy::shouldBreakPublication($exception)) {
                    $this->logger->info($exception->getMessage());

                    break;
                }

                if (MemberFacingStrategy::shouldContinuePublication($exception)) {
                    continue;
                }

                throw $exception;
            }
        }

        return $publishedMessages;
    }

    /**
     * @param OwnershipCollection $ownerships
     *
     * @return OwnershipCollection
     */
    private function findNextBatchOfListOwnerships(OwnershipCollection $ownerships): OwnershipCollection
    {
        $previousCursor = -1;

        if ($this->collectionStrategy->listRestriction()) {
            return $this->accessor->getUserOwnerships(
                $this->collectionStrategy->onBehalfOfWhom(),
                $ownerships->nextPage()
            );
        }

        while ($this->targetListHasNotBeenFound(
            $ownerships,
            $this->collectionStrategy->forWhichList()
        )) {
            $ownerships = $this->accessor->getUserOwnerships(
                $this->collectionStrategy->onBehalfOfWhom(),
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
                        $this->collectionStrategy->onBehalfOfWhom()
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
     * @throws InvalidSerializedTokenException
     */
    private function guardAgainstInvalidListName(
        OwnershipCollection $ownerships
    ): OwnershipCollection {
        if ($this->collectionStrategy->noListRestriction()) {
            return $ownerships;
        }

        $listRestriction = $this->collectionStrategy->forWhichList();
        if ($this->targetListHasNotBeenFound($ownerships, $listRestriction)) {
            $ownerships = $this->guardAgainstInvalidToken($ownerships);
        }

        if ($this->targetListHasNotBeenFound($ownerships, $listRestriction)) {
            $message = sprintf(
                'Invalid list name ("%s"). Could not be found',
                $listRestriction
            );
            $this->output->writeln($message);

            throw new InvalidListNameException($message);
        }

        return $ownerships;
    }

    /**
     * @param OwnershipCollection $ownerships
     *
     * @return OwnershipCollection
     * @throws InvalidSerializedTokenException
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
     * @param stdClass       $twitterUser
     * @param MemberIdentity $memberIdentity
     *
     * @param bool           $protected
     * @param bool           $suspended
     *
     * @return Member
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws InvalidMemberIdentifier
     */
    private function makeUser(
        stdClass $twitterUser,
        MemberIdentity $memberIdentity,
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
            $memberIdentity->id(),
            $twitterUser->screen_name,
            $protected,
            $suspended
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * @param $ownerships
     *
     * @return array
     */
    private function mapOwnershipsLists(OwnershipCollection $ownerships): array
    {
        return array_map(
            fn(PublicationList $list) => $list->name(),
            $ownerships->toArray()
        );
    }

    /**
     * @param PublicationList $list
     * @param TokenInterface  $messageBody
     *
     * @throws InvalidSerializedTokenException
     */
    private function processMemberList(
        PublicationList $list,
        TokenInterface $messageBody
    ) {
        if ($this->collectionStrategy->shouldProcessList($list)) {
            $memberCollection = $this->accessor->getListMembers($list->id());
            $memberCollection = $this->addOwnerToListOptionally($memberCollection);

            if ($memberCollection->isEmpty()) {
                EmptyListException::throws(
                    sprintf(
                        'List "%s" has no members',
                        $list->name()
                    )
                );
            }

            if ($memberCollection->isNotEmpty()) {
                $this->logger->info(
                    sprintf(
                        'About to publish messages for members in list "%s"',
                        $list->name()
                    )
                );
            }

            $publishedMessages = $this->dispatchMessages(
                $memberCollection,
                $messageBody,
                $list
            );

            // Change token for each list
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
                        '{{ list }}'  => $list->name(),
                    ]
                )
            );
        }
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    private function produceSearchStatusesMessages(): void
    {
        $searchQuery = $this->collectionStrategy->forWhichQuery();

        $savedSearch = $this->savedSearchRepository
            ->findOneBy(['searchQuery' => $searchQuery]);

        if (!($savedSearch instanceof SavedSearch)) {
            $response    = $this->accessor->saveSearch($searchQuery);
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
     * @throws InvalidListNameException
     * @throws InvalidSerializedTokenException
     */
    private function producesListMessages(): int
    {
        try {
            $memberOwnership = $this->ownershipAccessor->getOwnershipsForMemberHavingScreenNameAndToken(
                $this->collectionStrategy->onBehalfOfWhom(),
                Token::fromArray($this->getTokensFromInputOrFallback())
            );
        } catch (OverCapacityException $exception) {
            $this->output->writeln($exception->getMessage());

            return self::RETURN_STATUS_SUCCESS;
        }

        $ownerships = $this->guardAgainstInvalidListName(
            $memberOwnership->ownershipCollection()
        );

        foreach ($ownerships->toArray() as $list) {
            try {
                $this->processMemberList(
                    $list,
                    $memberOwnership->token()
                );
            } catch (EmptyListException $exception) {
                $this->logger->info($exception->getMessage());
            } catch (Exception $exception) {
                $this->logger->critical($exception->getMessage());

                return self::RETURN_STATUS_FAILURE;
            }
        }

        return self::RETURN_STATUS_SUCCESS;
    }

    /**
     * @throws InvalidSerializedTokenException
     */
    private function setUpDependencies(): void
    {
        $this->setUpLogger();

        $this->accessor->setAccessToken(
            Token::fromArray(
                $this->getTokensFromInputOrFallback()
            )
        );

        $this->setupAggregateRepository();

        if (
            $this->collectionStrategy->shouldPrioritizeLists()
            && ($this->collectionStrategy->listRestriction()
                || $this->collectionStrategy->shouldApplyListCollectionRestriction())
        ) {
            $this->dispatcher = $this->getContainer()
                                     ->get(
                                         'old_sound_rabbit_mq.weaving_the_web_amqp.twitter.aggregates_status_producer'
                                     );

            $this->likesMessagesDispatcher = $this->getContainer()
                                                  ->get(
                                                    'old_sound_rabbit_mq.weaving_the_web_amqp.producer.aggregates_likes_producer'
                                                );
        }

        if ($this->collectionStrategy->shouldSearchByQuery()) {
            $this->dispatcher = $this->getContainer()
                                     ->get(
                                         'old_sound_rabbit_mq.weaving_the_web_amqp.producer.search_matching_statuses_producer'
                                     );
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function shouldSkipOperation(): bool
    {
        return $this->operationClock->shouldSkipOperation()
            && $this->collectionStrategy->allListsAreEquivalent()
            && $this->collectionStrategy->noQueryRestriction();
    }

    /**
     * @param MemberIdentity $memberIdentity
     *
     * @throws SkippableMemberException
     */
    private function skipUnrestrictedMember(MemberIdentity $memberIdentity): void
    {
        if ($this->collectionStrategy->restrictDispatchToSpecificMember($memberIdentity)) {
            throw new SkippableMemberException(
                sprintf(
                    'Skipping "%s" as member restriction applies',
                    $memberIdentity->screenName()
                )
            );
        }
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
