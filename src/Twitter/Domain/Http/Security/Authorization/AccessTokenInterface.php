<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Http\Security\Authorization;

interface AccessTokenInterface
{
    public function token(): string;

    public function secret(): string;

    public function userId(): string;

    public function screenName(): string;
}