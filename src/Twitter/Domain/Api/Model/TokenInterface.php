<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Api\Model;

use DateTimeInterface;

interface TokenInterface
{
    public const FIELD_TOKEN = 'token';
    public const FIELD_SECRET = 'secret';
    
    public function getAccessToken(): string;

    public function getAccessTokenSecret(): string;

    public function getConsumerKey(): string;

    public function getConsumerSecret(): string;

    public function hasConsumerKey(): bool;

    public function setAccessToken(string $accessToken): TokenInterface;

    public function setAccessTokenSecret(string $accessTokenSecret): TokenInterface;

    public function setUpdatedAt(DateTimeInterface $date): TokenInterface;

    public function updatedAt(): DateTimeInterface;

    public function isValid(): bool;

    public function isFrozen(): bool;

    public function isNotFrozen(): bool;

    public function toArray(): array;

    public function freeze(): self;

    public function unfreeze(): self;

    public function nextFreezeEndsAt(): DateTimeInterface;

    public function firstIdentifierCharacters(): string;

    public static function fromArray(array $token): self;
}