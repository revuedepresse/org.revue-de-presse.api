<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Log;

use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use Psr\Log\LoggerInterface;

interface TweetCurationLoggerInterface extends LoggerInterface
{
    public function extractTweetReach(TweetInterface $memberStatus): array;

    public function logHowManyItemsHaveBeenCollected(
        CurationSelectorsInterface $selectors,
        int                        $totalStatuses,
        array                      $forms,
        int                        $lastCollectionBatchSize
    ): void;

    public function logHowManyItemsHaveBeenFetched(
        array $statuses,
        string $screenName
    ): void;

    public function logIntentionWithRegardToList(
        $options,
        CurationSelectorsInterface $selectors
    ): void;

    public function logStatus(TweetInterface $status): void;
}
