<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Api\Repository;

use App\Twitter\Infrastructure\Api\Entity\TokenType;

interface TokenTypeRepositoryInterface
{
    public function ensureTokenTypesExist(): void;

    public static function applicationTokenType(): TokenType;

    public static function userTokenType(): TokenType;
}