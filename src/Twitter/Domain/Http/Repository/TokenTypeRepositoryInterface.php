<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Http\Repository;

use App\Twitter\Infrastructure\Http\Entity\TokenType;

interface TokenTypeRepositoryInterface
{
    public function ensureTokenTypesExist(): void;

    public static function applicationTokenType(): TokenType;

    public static function userTokenType(): TokenType;
}