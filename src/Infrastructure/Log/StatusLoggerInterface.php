<?php
declare(strict_types=1);

namespace App\Infrastructure\Log;

use App\Domain\Status\StatusInterface;
use function sprintf;

interface StatusLoggerInterface
{
    public function logHowManyItemsHaveBeenFetched(array $statuses, string $screenName): void;

    public function logIntentionWithRegardsToAggregate($options, ?string $aggregateId = null): void;

    public function logStatus(StatusInterface $status): void;
}