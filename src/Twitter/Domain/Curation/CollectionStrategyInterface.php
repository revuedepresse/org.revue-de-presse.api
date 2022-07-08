<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation;

interface CollectionStrategyInterface
{
    public const MAX_AVAILABLE_TWEETS_PER_USER = 3200;

    public const MAX_BATCH_SIZE = 200;

    public function fetchLikes(): bool;

    public function maxStatusId();

    public function minStatusId();

    public function screenName(): string;

    public static function fromArray(array $options): self;
}
