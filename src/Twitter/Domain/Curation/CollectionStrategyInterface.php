<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation;

interface CollectionStrategyInterface
{
    public const MAX_AVAILABLE_TWEETS_PER_USER = 3200;

    public const MAX_BATCH_SIZE = 200;

    public function dateBeforeWhichPublicationsAreToBeCollected(): ?string;

    public function PublishersListId(): ?int;

    public function fetchLikes(): bool;

    public function maxStatusId();

    public function minStatusId();

    public function screenName(): string;

    public function oneOfTheOptionsIsActive(): bool;

    public function optInToCollectStatusFor(string $screenName): self;

    public function optInToCollectStatusWhichIdIsGreaterThan($minId): self;

    public function optInToCollectStatusWhichIdIsLessThan($maxId): self;

    public function optInToCollectStatusPublishedBefore(string $date): self;

    public function optInToCollectStatusForPublishersListOfId(
        ?int $publishersList = null
    ): self;

    public function optInToFetchLikes(bool $fetchLikes = false): self;

    public static function fromArray(array $options): self;
}