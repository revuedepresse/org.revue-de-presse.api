<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api;

use App\Twitter\Domain\Api\TwitterErrorAwareInterface;

interface UnavailableResourceInterface extends TwitterErrorAwareInterface
{
    public function getType(): int;
}