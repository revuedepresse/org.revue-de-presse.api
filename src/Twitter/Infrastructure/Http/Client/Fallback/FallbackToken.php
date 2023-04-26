<?php

namespace App\Twitter\Infrastructure\Http\Client\Fallback;

use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Infrastructure\Http\Entity\TokenTrait;
use DateTimeInterface;

class FallbackToken implements TokenInterface
{
    use TokenTrait;

    public function getAccessToken(): string
    {
        return 'dummy_access_token';
    }

    public function getAccessTokenSecret(): string
    {
        return 'dummy_access_secret';
    }

    public function getConsumerKey(): string
    {
        return 'dummy_fallback_consumer_key';
    }

    public function getConsumerSecret(): string
    {
        return 'dummy_fallback_consumer_secret';
    }

    public function hasConsumerKey(): bool
    {
        return true;
    }

    public function setAccessToken(string $accessToken): TokenInterface
    {
        return $this;
    }

    public function setAccessTokenSecret(string $accessTokenSecret): TokenInterface
    {
        return $this;
    }

    public function setUpdatedAt(DateTimeInterface $date): TokenInterface
    {
        return $this;
    }

    public function updatedAt(): DateTimeInterface
    {
        return new \DateTimeImmutable('now');
    }

    public function isValid(): bool
    {
        return true;
    }

    public function isFrozen(): bool
    {
        return false;
    }

    public function isNotFrozen(): bool
    {
        return !$this->isFrozen();
    }

    public function freeze(): TokenInterface
    {
        // noop

        return $this;
    }

    public function unfreeze(): TokenInterface
    {
        // noop

        return $this;
    }

    public function nextFreezeEndsAt(): DateTimeInterface
    {
        return new \DateTimeImmutable('now');
    }

    public function firstIdentifierCharacters(): string
    {
        return 'dummy_identifier';
    }

    public static function fromProps(array $accessTokenProps): TokenInterface
    {
        return new self();
    }
}