<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Entity;

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

    public function isNotFrozen(): bool
    {
        return false;
    }

    public function setFrozenUntil(\DateTimeInterface $frozenUntil): TokenInterface
    {
        return $this;
    }

    public function getFrozenUntil(): \DateTimeInterface
    {
        return new \DateTimeImmutable();
    }

    public function firstIdentifierCharacters(): string
    {
        return '';
    }
}