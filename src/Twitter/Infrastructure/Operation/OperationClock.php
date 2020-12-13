<?php

namespace App\Twitter\Infrastructure\Operation;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

class OperationClock
{
    /**
     * @var string
     */
    public string $timeAfterWhichOperationIsSkipped;

    /**
     * @var string
     */
    public string $timeBeforeWhichOperationIsSkipped;

    /**
     * @var LoggerInterface
     */
    public LoggerInterface $logger;

    /**
     * @return bool
     * @throws Exception
     */
    public function shouldSkipOperation(): bool
    {
        $now = new DateTime('now', new \DateTimeZone('UTC'));
        $today = $now->format('Y-m-d');
        $operationSkippedAfter = (new DateTime(
            $today.' '.$this->timeAfterWhichOperationIsSkipped
        ));
        $operationSkippedBefore = new DateTime(
            $today.' '.$this->timeBeforeWhichOperationIsSkipped
        );

        return $this->shouldSkipOperationBetween(
            $operationSkippedAfter,
            $operationSkippedBefore
        );
    }

    /**
     * @param DateTime $earliestDate
     * @param DateTime $latestDate
     *
     * @return bool
     * @throws Exception
     */
    private function shouldSkipOperationBetween(
        DateTime $earliestDate,
        DateTime $latestDate
    ) {
        $now = new DateTime('now', new \DateTimeZone('UTC'));
        $shouldSkip = $now >= $earliestDate && $now <= $latestDate;

        if ($shouldSkip) {
            $this->logger->info('Skipping operation');
        }

        return $shouldSkip;
    }
}
