<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Entity;

use App\Twitter\Domain\Api\Model\TokenInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class NullToken implements TokenInterface
{
    use TokenTrait;

    public function getOAuthToken(): string
    {
        return '';
    }

    public function getOAuthSecret(): string
    {
        return '';
    }

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

    public function getFrozenUntil(): \DateTimeInterface
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
}