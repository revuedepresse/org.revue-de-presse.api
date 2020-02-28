<?php
declare(strict_types=1);

namespace App\Infrastructure\Log;

use App\Api\Entity\Aggregate;
use App\Domain\Status\StatusInterface;
use App\Infrastructure\DependencyInjection\TranslatorTrait;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_key_exists;
use function count;
use function json_decode;
use function json_last_error;
use function sprintf;
use function str_pad;
use const JSON_ERROR_NONE;
use const JSON_THROW_ON_ERROR;

class StatusLogger implements StatusLoggerInterface
{
    use TranslatorTrait;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->translator = $translator;
        $this->logger = $logger;
    }

    public function logStatus(StatusInterface $status): void
    {
        $reach = $this->extractReachOfStatus($status);

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
                implode([
                    'https://twitter.com/',
                    $status->getScreenName(),
                    '/status/',
                    $status->getStatusId()
                ])
            )
        );
    }

    /**
     * @param int    $statusesCount
     * @param string $memberName
     * @param bool   $collectingLikes
     *
     * @return int
     */
    public function logHowManyItemsHaveBeenSaved(
        int $statusesCount,
        string $memberName,
        bool $collectingLikes
    ): int {
        if ($statusesCount > 0) {
            $messageKey = 'logs.info.status_saved';
            $total      = 'total_status';
            if ($collectingLikes) {
                $messageKey = 'logs.info.likes_saved';
                $total      = 'total_likes';
            }

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

    /**
     * @param StatusInterface $memberStatus
     *
     * @return array
     */
    private function extractReachOfStatus(StatusInterface $memberStatus): array
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

    /**
     * @param StatusInterface $memberStatus
     *
     * @return string
     */
    private function getStatusAggregate(StatusInterface $memberStatus): string
    {
        $aggregateName = 'without aggregate';
        if (!$memberStatus->getAggregates()->isEmpty()) {
            $aggregate = $memberStatus->getAggregates()->first();
            if ($aggregate instanceof Aggregate) {
                $aggregateName = $aggregate->getName();
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

    /**
     * @param array  $statuses
     * @param string $screenName
     */
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

    /**
     * @param             $options
     * @param string|null $aggregateId
     */
    public function logIntentionWithRegardsToAggregate($options, ?string $aggregateId = null): void
    {
        if ($aggregateId === null) {
            $this->logger->info(sprintf('No aggregate id for "%s"', $options['screen_name']));

            return;
        }

        $this->logger->info(
            sprintf(
                'About to save status for "%s" in aggregate #%d',
                $options['screen_name'],
                $aggregateId
            )
        );
    }
}