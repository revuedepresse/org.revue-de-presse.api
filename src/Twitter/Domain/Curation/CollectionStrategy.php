<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation;

use App\Twitter\Domain\Publication\Repository\LikedStatusRepositoryInterface;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Domain\Publication\Repository\StatusRepositoryInterface;
use App\Twitter\Domain\Curation\LikedStatusCollectionAwareInterface;
use function array_key_exists;
use const INF;

class CollectionStrategy implements CollectionStrategyInterface
{
    public static function fromArray(array $options): self
    {
        $strategy = new self();

        if (array_key_exists('aggregate_id', $options) && $options['aggregate_id']) {
            $strategy->optInToCollectStatusForPublishersListOfId(
                $options['aggregate_id']
            );
        }

        if (array_key_exists('before', $options) && $options['before']) {
            $strategy->optInToCollectStatusPublishedBefore($options['before']);
        }

        if (array_key_exists('screen_name', $options) && $options['screen_name']) {
            $strategy->optInToCollectStatusFor($options['screen_name']);
        }

        if (
            array_key_exists(
                LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES,
                $options
            )
            && $options[LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES]
        ) {
            $strategy->optInToFetchLikes(true);
        }

        return $strategy;
    }

    private ?string $dateBeforeWhichStatusAreCollected = null;

    private ?int $publishersListId = null;

    private bool $shouldFetchLikes = false;

    private string $screenName;

    private $maxStatusId;

    private $minStatusId;

    public function shouldLookUpPublicationsWithMinId(
        LikedStatusRepositoryInterface $likedStatusRepository,
        StatusRepositoryInterface $statusRepository,
        MemberRepositoryInterface $memberRepository
    ): bool {
        if ($this->fetchLikes()) {
            return $likedStatusRepository->countHowManyLikesFor($this->screenName())
                > self::MAX_AVAILABLE_TWEETS_PER_USER;
        }

        $minPublicationId = $memberRepository->getMinPublicationIdForMemberHavingScreenName(
            $this->screenName()
        );

        if ($minPublicationId) {
            return true;
        }

        return $statusRepository->countHowManyStatusesFor($this->screenName())
            > self::MAX_AVAILABLE_TWEETS_PER_USER;
    }

    public function dateBeforeWhichPublicationsAreToBeCollected(): ?string
    {
        return $this->dateBeforeWhichStatusAreCollected;
    }

    public function fetchLikes(): bool
    {
        return $this->shouldFetchLikes;
    }

    public function maxStatusId()
    {
        if ($this->maxStatusId === null) {
            return INF;
        }

        return $this->maxStatusId;
    }

    public function minStatusId()
    {
        if ($this->minStatusId === null) {
            return -INF;
        }

        return $this->minStatusId;
    }

    public function oneOfTheOptionsIsActive(): bool
    {
        return $this->publishersListId()
            || $this->dateBeforeWhichPublicationsAreToBeCollected();
    }

    public function optInToCollectStatusFor(string $screenName): CollectionStrategyInterface
    {
        $this->screenName = $screenName;

        return $this;
    }

    public function optInToCollectStatusForPublishersListOfId(
        ?int $publishersListId = null
    ): self {
        $this->publishersListId = $publishersListId;

        return $this;
    }

    public function optInToCollectStatusPublishedBefore(string $date): self
    {
        $this->dateBeforeWhichStatusAreCollected = $date;

        return $this;
    }

    public function optInToCollectStatusWhichIdIsLessThan($maxStatusId): CollectionStrategyInterface
    {
        $this->maxStatusId = $maxStatusId;

        return $this;
    }

    public function optInToCollectStatusWhichIdIsGreaterThan($minStatusId): CollectionStrategyInterface
    {
        $this->minStatusId = $minStatusId;

        return $this;
    }

    public function optInToFetchLikes(?bool $fetchLikes = false): self
    {
        $this->shouldFetchLikes = $fetchLikes;

        return $this;
    }

    public function PublishersListId(): ?int
    {
        return $this->publishersListId;
    }

    public function screenName(): string
    {
        return $this->screenName;
    }
}