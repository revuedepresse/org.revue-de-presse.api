<?php
declare(strict_types=1);

namespace App\Membership\Domain\Model;

use App\Twitter\Domain\Http\Model\TokenInterface;
use Doctrine\Common\Collections\Selectable;
use Symfony\Component\Security\Core\User\UserInterface;

interface MemberInterface extends UserInterface
{
    public function getApiKey(): ?string;

    public function getId(): ?int;

    public function getUsername(): ?string;

    public function setUsername(string $username): MemberInterface;

    public function setEmail(string $email): MemberInterface;

    public function setTwitterID(string $twitterId): MemberInterface;

    public function twitterId(): ?string;

    public function getUserIdentifier(): string;

    public function setTwitterScreenName(string $twitterScreenName): MemberInterface;

    public function twitterScreenName(): ?string;

    public function setLastStatusPublicationDate(\DateTimeInterface $lastStatusPublicationDate): MemberInterface;

    /**
     * @deprecated
     */
    public function setFullName(string $fullName): MemberInterface;

    public function getFullName(): string;

    public function setProtected(bool $protected): MemberInterface;

    public function isProtected(): bool;

    public function isNotProtected(): bool;

    public function setSuspended(bool $suspended): MemberInterface;

    public function isSuspended(): bool;

    public function isNotSuspended(): bool;

    public function setNotFound(bool $notFound): MemberInterface;

    public function hasBeenDeclaredAsNotFound(): bool;

    public function hasNotBeenDeclaredAsNotFound(): bool;

    /** @deprecated */
    public function isAWhisperer(): bool;

    public function isLowVolumeTweetWriter(): bool;

    public function getDescription(): ?string;

    public function getUrl(): ?string;

    public function totalLikes(): int;

    public function setTotalLikes(int $totalLikes): MemberInterface;

    public function totalTweets(): int;

    public function setTotalTweets($totalTweets): MemberInterface;

    public function getMinStatusId(): int;

    public function addToken(TokenInterface $token): MemberInterface;

    public function getTokens(): Selectable;
}
