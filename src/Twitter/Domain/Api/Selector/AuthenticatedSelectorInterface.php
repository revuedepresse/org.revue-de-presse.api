<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Api\Selector;

use App\Twitter\Domain\Api\Model\TokenInterface;

interface AuthenticatedSelectorInterface
{
    public function screenName(): string;

    public function authenticationToken(): TokenInterface;
}