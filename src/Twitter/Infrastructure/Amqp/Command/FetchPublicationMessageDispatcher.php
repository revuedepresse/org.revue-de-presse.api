<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Command;

use App\PublishersList\Entity\SavedSearch;
use App\PublishersList\Repository\SavedSearchRepository;
use App\PublishersList\Repository\SearchMatchingStatusRepository;
use App\Twitter\Infrastructure\Amqp\Exception\SkippableOperationException;
use App\Twitter\Infrastructure\Amqp\Exception\UnexpectedOwnershipException;
use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Api\Exception\InvalidSerializedTokenException;
use App\Twitter\Domain\Curation\PublicationStrategyInterface;
use App\Twitter\Infrastructure\DependencyInjection\OwnershipAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublicationMessageDispatcherTrait;
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Infrastructure\InputConverter\InputToCollectionStrategy;
use App\Twitter\Infrastructure\Operation\OperationClock;
use App\Twitter\Infrastructure\Exception\OverCapacityException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package App\Twitter\Infrastructure\Amqp\Command
 */
class FetchPublicationMessageDispatcher extends AggregateAwareCommand
{
    private const OPTION_BEFORE                 = PublicationStrategyInterface::RULE_BEFORE;
    private const OPTION_SCREEN_NAME            = PublicationStrategyInterface::RULE_SCREEN_NAME;
    private const OPTION_MEMBER_RESTRICTION     = PublicationStrategyInterface::RULE_MEMBER_RESTRICTION;
    private const OPTION_INCLUDE_OWNER          = PublicationStrategyInterface::RULE_INCLUDE_OWNER;
    private const OPTION_IGNORE_WHISPERS        = PublicationStrategyInterface::RULE_IGNORE_WHISPERS;
    private const OPTION_QUERY_RESTRICTION      = PublicationStrategyInterface::RULE_QUERY_RESTRICTION;
    private const OPTION_PRIORITY_TO_AGGREGATES = PublicationStrategyInterface::RULE_PRIORITY_TO_AGGREGATES;
    private const OPTION_LIST                   = PublicationStrategyInterface::RULE_LIST;
    private const OPTION_LISTS                  = PublicationStrategyInterface::RULE_LISTS;
    private const OPTION_FETCH_LIKES            = PublicationStrategyInterface::RULE_FETCH_LIKES;
    private const OPTION_CURSOR                 = PublicationStrategyInterface::RULE_CURSOR;

    private const OPTION_OAUTH_TOKEN  = 'oauth_token';
    private const OPTION_OAUTH_SECRET = 'oauth_secret';

    use OwnershipAccessorTrait;
    use PublicationMessageDispatcherTrait;
    use TranslatorTrait;

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
     * @var PublicationStrategyInterface
     */
    private PublicationStrategyInterface $collectionStrategy;

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
            )
            ->addOption(
                self::OPTION_CURSOR,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Cursor from which ownership are to be fetched'
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
            )->addOption(
                self::OPTION_FETCH_LIKES,
                'fl',
                InputOption::VALUE_NONE,
                'Should fetch likes'
            )->setAliases(['pr:d-m-t-f-m-s']);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;

        $this->collectionStrategy = InputToCollectionStrategy::convertInputToCollectionStrategy($input);

        try {
            $this->setUpDependencies();
        } catch (SkippableOperationException $exception) {
            $this->output->writeln($exception->getMessage());
        } catch (InvalidSerializedTokenException $exception) {
            $this->logger->info($exception->getMessage());

            return self::RETURN_STATUS_FAILURE;
        }

        if ($this->collectionStrategy->shouldSearchByQuery()) {
            $this->produceSearchStatusesMessages();
        }

        if ($this->collectionStrategy->shouldNotSearchByQuery()) {
            $returnStatus = self::RETURN_STATUS_FAILURE;
            try {
                $this->publicationMessageDispatcher->dispatchPublicationMessages(
                    $this->collectionStrategy,
                    Token::fromArray($this->getTokensFromInputOrFallback()),
                    function ($message) {
                        $this->output->writeln($message);
                    }
                );
                $returnStatus = self::RETURN_STATUS_SUCCESS;
            } catch (UnexpectedOwnershipException|OverCapacityException $exception) {
                $this->logger->error(
                    $exception->getMessage(),
                    ['stacktrace' => $exception->getTraceAsString()]
                );
            } catch (\Throwable $exception) {
                $this->logger->error(
                    $exception->getMessage(),
                    ['stacktrace' => $exception->getTraceAsString()]
                );
            } finally {
                return $returnStatus;
            }
        }

        return self::RETURN_STATUS_SUCCESS;
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
     * @throws InvalidSerializedTokenException
     */
    private function setUpDependencies(): void
    {
        if ($this->shouldSkipOperation()) {
            SkippableOperationException::throws('This operation has to be skipped.');
        }

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
            // TODO customize message to be dispatched
            // Before introducting messenger component
            // it produced messages with
            // old_sound_rabbit_mq.weaving_the_web_amqp.twitter.aggregates_status_producer
            // old_sound_rabbit_mq.weaving_the_web_amqp.producer.aggregates_likes_producer
            // services
        }

        if ($this->collectionStrategy->shouldSearchByQuery()) {
            // TODO customize message to be dispatched
            // Before introducing messenger component
            // it produced messages with
            // old_sound_rabbit_mq.weaving_the_web_amqp.producer.search_matching_statuses_producer
            // service
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
}
