<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation;

interface CurationSelectorsInterface
{
    public const MAX_AVAILABLE_TWEETS_PER_USER = 3200;

    public const MAX_BATCH_SIZE = 200;

    public function curateTweetsBySearchQuery(string $searchQuery): self;

    public function dateBeforeWhichPublicationsAreToBeCollected(): ?string;

    public function isSearchQuery(): bool;

    public function membersListId(): ?int;

    public function maxStatusId();

    public function minStatusId();

    public function searchQuery(): string;

    public function screenName(): string;

    public function oneOfTheOptionsIsActive(): bool;

    public function selectTweetsByMemberScreenName(string $screenName): self;

    public function optInToCollectStatusWhichIdIsGreaterThan($minTweetId): self;

    public function optInToCollectStatusWhichIdIsLessThan($maxTweetId): self;

    public function optInToCollectStatusPublishedBefore(string $date): self;

    public function optInToCollectStatusForPublishersListOfId(
        ?int $publishersList = null
    ): self;

    public static function fromArray(array $options): self;
}
