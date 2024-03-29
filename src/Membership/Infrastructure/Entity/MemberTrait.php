<?php

namespace App\Membership\Infrastructure\Entity;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Selectable;

trait MemberTrait
{
    public function getApiKey(): string
    {
        return '';
    }

    public function getId(): int
    {
        return 0;
    }

    public function setGroupId(int $groupId): MemberInterface
    {
        return $this;
    }

    public function getUsername(): ?string
    {
    }

    public function setFirstName(string $firstName): MemberInterface
    {
        return $this;
    }

    public function getFirstName(): string
    {
        return '';
    }

    public function setLastName(string $lastName): MemberInterface
    {
        return $this;
    }

    public function getLastName(): string
    {
        return '';
    }

    public function setTwitterScreenName(string $twitterUsername): MemberInterface
    {
        return $this;
    }

    public function twitterScreenName(): string
    {
        return '';
    }

    public function twitterId(): ?string
    {
        return '';
    }

    public function getUserIdentifier(): string
    {
        return $this->twitterId();
    }

    public function setLastStatusPublicationDate(\DateTimeInterface $lastStatusPublicationDate): MemberInterface {
        return $this;
    }

    /**
     * @deprecated
     */
    public function setFullName(string $fullName): MemberInterface
    {
        return $this->setTwitterScreenName($fullName);
    }

    public function getFullName(): string
    {
        return '';
    }

    public function setProtected(bool $protected): MemberInterface
    {
        return $this;
    }

    public function isProtected(): bool
    {
        return false;
    }

    public function isNotProtected(): bool
    {
        return true;
    }

    public function setSuspended(bool $suspended): MemberInterface
    {
        return $this;
    }

    public function isSuspended(): bool
    {
        return false;
    }

    public function isNotSuspended(): bool
    {
        return true;
    }

    public function setNotFound(bool $notFound): MemberInterface
    {
        return $this;
    }

    public function hasBeenDeclaredAsNotFound(): bool
    {
        return false;
    }

    public function hasNotBeenDeclaredAsNotFound(): bool
    {
        return true;
    }

    /**
     * @deprecated
     */
    public function isAWhisperer(): bool
    {
        return false;
    }

    public function isLowVolumeTweetWriter(): bool
    {
        return $this->isAWhisperer();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function totalLikes(): int
    {
        return 0;
    }

    public function setTotalLikes(int $totalLikes): MemberInterface
    {
        return $this;
    }

    public function totalTweets(): int
    {
        return 0;
    }

    public function setTotalTweets($totalTweets): MemberInterface
    {
        return $this;
    }

    public function minTweetId(): int
    {
        return 0;
    }

    public function maxTweetId(): int
    {
        return 0;
    }

    public function setMaxTweetId(?int $tweetId): MemberInterface {
        // noop

        return $this;
    }

    public function setMinTweetId(?string $minTweetId): MemberInterface {
        // noop

        return $this;
    }

    public function setTwitterID(string $twitterId): MemberInterface
    {
        $this->twitterID = $twitterId;

        return $this;
    }

    public function hasTwitterId(): bool
    {
        return $this->twitterID === null;
    }

    public function addToken(TokenInterface $token): MemberInterface
    {
        return $this;
    }

    public function getTokens(): Selectable
    {
        return new ArrayCollection([]);
    }

    public function rawDocument(): array {
        return [];
    }

    public function setRawDocument(string $rawDocument): MemberInterface
    {
        return $this;
    }

    public function setUrl(string $url): MemberInterface
    {
        return $this;
    }

    public function setDescription(string $description): MemberInterface
    {
        return $this;
    }
}
