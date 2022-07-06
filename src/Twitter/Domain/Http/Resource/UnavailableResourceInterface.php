<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Http\Resource;

use App\Twitter\Domain\Http\ApiErrorCodeAwareInterface;

interface UnavailableResourceInterface extends ApiErrorCodeAwareInterface
{
    public function getType(): int;
}