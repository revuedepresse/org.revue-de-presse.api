<?php
declare(strict_types=1);

namespace App\Domain\Collection;

use App\Status\LikedStatusCollectionAwareInterface;
use function array_key_exists;
use const INF;

class CollectionStrategy implements CollectionStrategyInterface
{
    private ?string $dateBeforeWhichStatusAreCollected = null;

    private ?int $publicationListId = null;

    private bool $shouldFetchLikes = false;

    private string $screenName;

    private $maxStatusId;

    public function dateBeforeWhichStatusAreToBeCollected(): ?string
    {
        return $this->dateBeforeWhichStatusAreCollected;
    }

    public function optInToCollectStatusPublishedBefore(string $date): self
    {
        $this->dateBeforeWhichStatusAreCollected = $date;

        return $this;
    }

    public function publicationListId(): ?int
    {
        return $this->publicationListId;
    }

    public function optInToCollectStatusForPublicationListOfId(
        ?int $publicationListId = null
    ): self {
        $this->publicationListId = $publicationListId;

        return $this;
    }

    public function fetchLikes(): bool
    {
        return $this->shouldFetchLikes;
    }

    public function optInToFetchLikes(?bool $fetchLikes = false): self
    {
        $this->shouldFetchLikes = $fetchLikes;

        return $this;
    }

    public function oneOfTheOptionsIsActive(): bool
    {
        return $this->publicationListId() ||
            $this->dateBeforeWhichStatusAreToBeCollected();
    }

    public static function fromArray(array $options): self
    {
        $strategy = new self();

        if (array_key_exists('aggregate_id', $options) && $options['aggregate_id']) {
            $strategy->optInToCollectStatusForPublicationListOfId(
                $options['aggregate_id']
            );
        }

        if (array_key_exists('before', $options) && $options['before']) {
            $strategy->optInToCollectStatusPublishedBefore($options['before']);
        }

        if (array_key_exists(
            LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES,
            $options
        )) {
            $strategy->optInToFetchLikes(true);
        }

        return $strategy;
    }

    public function screenName(): string
    {
        return $this->screenName;
    }

    public function optInToCollectStatusFor(string $screenName): CollectionStrategyInterface
    {
        $this->screenName = $screenName;

        return $this;
    }

    public function maxStatusId()
    {
        if ($this->maxStatusId === null) {
            return INF;
        }

        return $this->maxStatusId;
    }

    public function optInToCollectStatusWhichIdIsLessThan($maxStatusId): CollectionStrategyInterface
    {
        $this->maxStatusId = $maxStatusId;

        return $this;
    }
}