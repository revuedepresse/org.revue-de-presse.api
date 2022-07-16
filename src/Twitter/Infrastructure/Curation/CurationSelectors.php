<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation;

use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Publication\Repository\TweetRepositoryInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchAuthoredTweetInterface;
use Assert\Assert;
use function array_key_exists;

class CurationSelectors implements CurationSelectorsInterface
{
    public static function fromArray(array $options): self
    {
        $selectors = new self();

        if (array_key_exists(FetchAuthoredTweetInterface::TWITTER_LIST_ID, $options) && $options[FetchAuthoredTweetInterface::TWITTER_LIST_ID]) {
            $selectors->optInToCollectStatusForPublishersListOfId(
                $options[FetchAuthoredTweetInterface::TWITTER_LIST_ID]
            );
        }

        if (array_key_exists(FetchAuthoredTweetInterface::BEFORE, $options) && $options[FetchAuthoredTweetInterface::BEFORE]) {
            $selectors->optInToCollectStatusPublishedBefore($options[FetchAuthoredTweetInterface::BEFORE]);
        }

        if (array_key_exists(FetchAuthoredTweetInterface::SCREEN_NAME, $options) && $options[FetchAuthoredTweetInterface::SCREEN_NAME]) {
            $selectors->selectTweetsByMemberScreenName($options[FetchAuthoredTweetInterface::SCREEN_NAME]);
        }

        return $selectors;
    }

    private ?string $dateBeforeWhichStatusAreCollected = null;

    private ?int $publishersListId = null;

    private string $memberSelectorByScreenName;

    private int $maxTweetId = PHP_INT_MAX;

    private int $minTweetId = PHP_INT_MIN;

    public function shouldLookUpPublicationsWithMinId(
        TweetRepositoryInterface  $tweetRepository,
        MemberRepositoryInterface $memberRepository
    ): bool {
        $minPublicationId = $memberRepository->getMinPublicationIdForMemberHavingScreenName(
            $this->screenName()
        );

        if ($minPublicationId) {
            return true;
        }

        return $tweetRepository->countHowManyStatusesFor($this->screenName())
            > self::MAX_AVAILABLE_TWEETS_PER_USER;
    }

    public function dateBeforeWhichPublicationsAreToBeCollected(): ?string
    {
        return $this->dateBeforeWhichStatusAreCollected;
    }

    public function maxStatusId(): int
    {
        return $this->maxTweetId;
    }

    public function minStatusId(): int
    {
        return $this->minTweetId;
    }

    public function oneOfTheOptionsIsActive(): bool
    {
        return $this->membersListId()
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

    public function optInToCollectStatusWhichIdIsLessThan($maxTweetId): CurationSelectorsInterface
    {
        $this->maxTweetId = $maxTweetId;

        return $this;
    }

    public function optInToCollectStatusWhichIdIsGreaterThan($minTweetId): CurationSelectorsInterface
    {
        $this->minTweetId = $minTweetId;

        return $this;
    }

    public function membersListId(): ?int
    {
        return $this->publishersListId;
    }

    public function screenName(): string
    {
        return $this->memberSelectorByScreenName;
    }
}
