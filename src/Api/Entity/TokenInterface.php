<?php
declare(strict_types=1);

namespace App\Api\Entity;

use App\Api\Exception\InvalidSerializedTokenException;

/**
 * @package App\Api\Entity
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

    public function toArray(): array;

    /**
     * @param array $token
     *
     * @throws InvalidSerializedTokenException
     *
     * @return static
     */
    public static function fromArray(array $token): self;
}