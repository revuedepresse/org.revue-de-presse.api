<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Entity;

use App\Twitter\Domain\Http\Model\TokenInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class NullToken implements TokenInterface
{
    use TokenTrait;

    public function getConsumerKey(): string
    {
        return '';
    }

    public function getConsumerSecret(): string
    {
        return '';
    }

    public function hasConsumerKey(): bool
    {
        return false;
    }

    public static function fromArray(array $token): self
    {
        return new self();
    }

    public function isValid(): bool
    {
        return false;
    }

    public function isFrozen(): bool
    {
        return true;
    }

    public function isNotFrozen(): bool
    {
        return !$this->isFrozen();
    }

    public function getFrozenUntil(): ?\DateTimeInterface
    {
        return $this->nextFreezeEndsAt();
    }

    public function firstIdentifierCharacters(): string
    {
        return '';
    }

    public function freeze(): TokenInterface
    {
        // no oop, can not freeze a null token
        return $this;
    }

    public function unfreeze(): TokenInterface
    {
        // no oop, can not unfreeze a null token
        return $this;
    }

    public function nextFreezeEndsAt(): DateTimeInterface
    {
        return new DateTimeImmutable(
            'now + 10 years',
            new DateTimeZone('UTC')
        );
    }

    private string $accessToken = '';

    public function setAccessToken(string $accessToken): TokenInterface
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    private string $accessTokenSecret = '';

    public function setAccessTokenSecret(string $accessTokenSecret): TokenInterface
    {
        $this->accessTokenSecret = $accessTokenSecret;

        return $this;
    }

    public function getAccessTokenSecret(): string
    {
        return $this->accessTokenSecret;
    }

    private DateTimeInterface $updatedAt;

    public function setUpdatedAt(DateTimeInterface $date): TokenInterface
    {
        $this->updatedAt =  $date;

        return $this;
    }

    public function updatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }
}