<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Api\Security\Authorization;

interface RequestTokenInterface
{
    public function token(): string;

    public function secret(): string;
}