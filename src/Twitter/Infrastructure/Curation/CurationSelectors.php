<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation;

use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Domain\Publication\Repository\StatusRepositoryInterface;
use Assert\Assert;
use function array_key_exists;
use const INF;

class CurationSelectors implements CurationSelectorsInterface
{
    public static function fromArray(array $options): self
    {
        $selectors = new self();

        if (array_key_exists('aggregate_id', $options) && $options['aggregate_id']) {
            $selectors->optInToCollectStatusForPublishersListOfId(
                $options['aggregate_id']
            );
        }

        if (array_key_exists('before', $options) && $options['before']) {
            $selectors->optInToCollectStatusPublishedBefore($options['before']);
        }

        if (array_key_exists('screen_name', $options) && $options['screen_name']) {
            $selectors->selectTweetsByMemberScreenName($options['screen_name']);
        }

        return $selectors;
    }

    private ?string $dateBeforeWhichStatusAreCollected = null;

    private ?int $publishersListId = null;

    private string $memberSelectorByScreenName;

    private $maxStatusId;

    private $minStatusId;

    public function shouldLookUpPublicationsWithMinId(
        StatusRepositoryInterface $statusRepository,
        MemberRepositoryInterface $memberRepository
    ): bool {
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

    public function selectTweetsByMemberScreenName(string $screenName): CurationSelectorsInterface
    {
        Assert::lazy()
            ->that($screenName)
                ->notEmpty()
            ->verifyNow();

        $this->memberSelectorByScreenName = strtolower($screenName);

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

    public function optInToCollectStatusWhichIdIsLessThan($maxStatusId): CurationSelectorsInterface
    {
        $this->maxStatusId = $maxStatusId;

        return $this;
    }

    public function optInToCollectStatusWhichIdIsGreaterThan($minStatusId): CurationSelectorsInterface
    {
        $this->minStatusId = $minStatusId;

        return $this;
    }

    public function PublishersListId(): ?int
    {
        return $this->publishersListId;
    }

    public function screenName(): string
    {
        return $this->memberSelectorByScreenName;
    }
}