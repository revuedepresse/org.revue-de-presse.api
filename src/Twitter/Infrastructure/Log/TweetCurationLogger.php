<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Log;

use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Infrastructure\Curation\TweetCurator;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_key_exists;
use function count;
use function is_infinite;
use function json_decode;
use function json_last_error;
use function sprintf;
use function str_pad;
use const JSON_ERROR_NONE;
use const JSON_THROW_ON_ERROR;

class TweetCurationLogger implements TweetCurationLoggerInterface
{
    use TranslatorTrait;

    private LoggerInterface $logger;

    public function __construct(
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->translator = $translator;
        $this->logger     = $logger;
    }

    /**
     * @throws JsonException
     */
    public function extractTweetReach(TweetInterface $memberStatus): array
    {
        $decodedApiResponse = json_decode(
            $memberStatus->getApiDocument(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $favoriteCount = 0;
        $retweetCount  = 0;
        if (json_last_error() === JSON_ERROR_NONE) {
            if (array_key_exists('favorite_count', $decodedApiResponse)) {
                $favoriteCount = $decodedApiResponse['favorite_count'];
            }

            if (array_key_exists('retweet_count', $decodedApiResponse)) {
                $retweetCount = $decodedApiResponse['retweet_count'];
            }
        }

        return [
            'favorite_count' => $favoriteCount,
            'retweet_count'  => $retweetCount
        ];
    }

    public function logHowManyItemsHaveBeenCollected(
        CurationSelectorsInterface $selectors,
        int                        $totalStatuses,
        array                      $forms,
        int                        $batchSize
    ): void {
        if ($selectors->minStatusId()) {
            $extremumId = $selectors->minStatusId();
            if (is_infinite($selectors->minStatusId())) {
                $extremumId = '-infinity';
            }
        }

        if ($selectors->maxStatusId()) {
            $extremumId = $selectors->maxStatusId();
            if (is_infinite($selectors->maxStatusId())) {
                $extremumId = '+infinity';
            }
        }

        $this->logger->info(
            sprintf(
                '%d %s older than %s of id #%d have been found for "%s"',
                $totalStatuses,
                $forms['plural'],
                $forms['singular'],
                $extremumId,
                $selectors->screenName()
            )
        );

        $this->logCollectionProgress(
            $selectors,
            $batchSize,
            $totalStatuses
        );
    }

    public function logHowManyItemsHaveBeenFetched(
        array $statuses,
        string $screenName
    ): void {
        $this->logger->info(
            sprintf(
                'Fetched "%d" statuses for "%s"',
                count($statuses),
                $screenName
            )
        );
    }

    public function logHowManyItemsHaveBeenSaved(
        int $statusesCount,
        string $memberName
    ): int {
        if ($statusesCount > 0) {
            $messageKey = 'logs.info.status_saved';
            $total      = 'total_status';

            $savedTweets = $this->translator->trans(
                $messageKey,
                [
                    'count'  => $statusesCount,
                    'member' => $memberName,
                    $total   => $statusesCount,
                ],
                'logs'
            );

            $this->logger->info($savedTweets);

            return $statusesCount;
        }

        $this->logger->info(sprintf('Nothing new for "%s"', $memberName));

        return 0;
    }

    public function logIntentionWithRegardToList(
        $options,
        CurationSelectorsInterface $selectors
    ): void {
        if ($selectors->membersListId() === null) {
            $this->logger->info(sprintf(
                'No aggregate id for "%s"', $options['screen_name']
            ));

            return;
        }

        $this->logger->info(
            sprintf(
                'About to save status for "%s" in aggregate #%d',
                $options['screen_name'],
                $selectors->membersListId()
            )
        );
    }

    public function logStatus(TweetInterface $status): void
    {
        $reach = $this->extractTweetReach($status);

        $favoriteCount = $reach['favorite_count'];
        $retweetCount  = $reach['retweet_count'];

        $this->logger->info(
            sprintf(
                '%s |_%s_| "%s" | @%s | %s | %s ',
                $status->getCreatedAt()->format('Y-m-d H:i'),
                str_pad($this->getStatusRelevance($retweetCount, $favoriteCount), 4, ' '),
                $this->getStatusAggregate($status),
                $status->getScreenName(),
                $status->getText(),
                implode(
                    [
                        'https://twitter.com/',
                        $status->getScreenName(),
                        '/status/',
                        $status->getStatusId()
                    ]
                )
            )
        );
    }

    private function logCollectionProgress(
        CurationSelectorsInterface $selectors,
        int                        $lastCollectionBatchSize,
        int                        $totalCollectedStatuses
    ): void {
        $subject = 'statuses';

        if ($this->collectedAllAvailableStatuses(
            $lastCollectionBatchSize,
            $totalCollectedStatuses)
        ) {
            $this->logger->info(
                sprintf(
                    'All available %s have most likely been fetched for "%s" or few %s are available (%d)',
                    $subject,
                    $selectors->screenName(),
                    $subject,
                    $totalCollectedStatuses
                )
            );

            return;
        }

        $this->logger->info(
            sprintf(
                '%d more %s in the past have been saved for "%s" in aggregate #%d',
                $lastCollectionBatchSize,
                $subject,
                $selectors->screenName(),
                $selectors->membersListId()
            )
        );
    }

    /**
     * @param $lastCollectionBatchSize
     * @param $totalCollectedStatuses
     *
     * @return bool
     */
    public function collectedAllAvailableStatuses($lastCollectionBatchSize, $totalCollectedStatuses): bool
    {
        return $this->didNotCollectedAnyStatus($lastCollectionBatchSize)
            && $this->hitCollectionLimit($totalCollectedStatuses);
    }

    /**
     * @param $statuses
     *
     * @return bool
     */
    public function didNotCollectedAnyStatus($statuses): bool
    {
        return $statuses === null || $statuses === 0;
    }

    /**
     * @param $statuses
     *
     * @return bool
     */
    public function hitCollectionLimit($statuses): bool
    {
        return $statuses >= (CurationSelectorsInterface::MAX_AVAILABLE_TWEETS_PER_USER - 100);
    }

    /**
     * @param TweetInterface $memberStatus
     *
     * @return string
     */
    private function getStatusAggregate(TweetInterface $memberStatus): string
    {
        $aggregateName = 'without aggregate';
        if (!$memberStatus->getAggregates()->isEmpty()) {
            $aggregate = $memberStatus->getAggregates()->first();
            if ($aggregate instanceof PublishersList) {
                $aggregateName = $aggregate->name();
            }
        }

        return $aggregateName;
    }

    /**
     * @param $retweetCount
     * @param $favoriteCount
     *
     * @return string
     */
    private function getStatusRelevance($retweetCount, $favoriteCount): string
    {
        if ($retweetCount > 1000 || $favoriteCount > 1000) {
            return '!!!!';
        }

        if ($retweetCount > 100 || $favoriteCount > 100) {
            return '_!!!';
        }

        if ($retweetCount > 10 || $favoriteCount > 10) {
            return '__!!';
        }

        if ($retweetCount > 0 || $favoriteCount > 0) {
            return '___!';
        }

        return '____';
    }

    public function emergency(\Stringable|string $message, array $context = [])
    {
        $this->logger->emergency($message, $context);
    }

    public function alert(\Stringable|string $message, array $context = [])
    {
        $this->logger->alert($message, $context);
    }

    public function critical(\Stringable|string $message, array $context = [])
    {
        $this->logger->critical($message, $context);
    }

    public function error(\Stringable|string $message, array $context = [])
    {
        $this->logger->error($message, $context);
    }

    public function warning(\Stringable|string $message, array $context = [])
    {
        $this->logger->warning($message, $context);
    }

    public function notice(\Stringable|string $message, array $context = [])
    {
        $this->logger->notice($message, $context);
    }

    public function info(\Stringable|string $message, array $context = [])
    {
        $this->logger->info($message, $context);
    }

    public function debug(\Stringable|string $message, array $context = [])
    {
        $this->logger->debug($message, $context);
    }

    public function log($level, \Stringable|string $message, array $context = [])
    {
        $this->logger->log($level, $message, $context);
    }
}
