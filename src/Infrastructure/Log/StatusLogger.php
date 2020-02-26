<?php
declare(strict_types=1);

namespace App\Infrastructure\Log;

use App\Api\Entity\Aggregate;
use App\Domain\Status\StatusInterface;
use Psr\Log\LoggerInterface;
use function array_key_exists;
use function json_decode;
use function json_last_error;
use function sprintf;
use function str_pad;
use const JSON_ERROR_NONE;
use const JSON_THROW_ON_ERROR;

class StatusLogger implements StatusLoggerInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
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
     * @param StatusInterface $memberStatus
     *
     * @return array
     */
    public function extractReachOfStatus(StatusInterface $memberStatus): array
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
}