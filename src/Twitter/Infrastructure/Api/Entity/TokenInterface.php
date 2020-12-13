<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Entity;

/**
 * @package App\Twitter\Infrastructure\Api\Entity
 */
interface TokenInterface
{
    public const FIELD_TOKEN = 'token';
    public const FIELD_SECRET = 'secret';

    public function getOAuthToken(): string;

    public function getOAuthSecret(): string;

    public function getConsumerKey(): string;

    public function getConsumerSecret(): string;

    public function hasConsumerKey(): bool;

    public function isValid(): bool;

    public function isNotFrozen(): bool;

    public function toArray(): array;

    public function getFrozenUntil(): \DateTimeInterface;

    public function setFrozenUntil(\DateTimeInterface $frozenUntil): self;

    public function firstIdentifierCharacters(): string;

    public static function fromArray(array $token): self;
}