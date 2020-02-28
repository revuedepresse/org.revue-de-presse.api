<?php
declare(strict_types=1);

namespace App\Domain\Collection;

use App\Status\LikedStatusCollectionAwareInterface;
use function array_key_exists;

class CollectionStrategy implements CollectionStrategyInterface
{
    private ?string $dateBeforeWhichStatusAreCollected = null;

    private ?int $publicationListId = null;

    private bool $shouldFetchLikes = false;

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
}