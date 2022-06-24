<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Http\Selector;

use App\Twitter\Domain\Http\Model\TokenInterface;

interface AuthenticatedSelectorInterface
{
    public function screenName(): string;

    public function authenticationToken(): TokenInterface;
}