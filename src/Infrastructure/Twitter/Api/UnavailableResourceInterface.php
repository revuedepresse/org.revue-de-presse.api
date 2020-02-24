<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Api;

use App\Twitter\Api\TwitterErrorAwareInterface;

interface UnavailableResourceInterface extends TwitterErrorAwareInterface
{
    public function getType(): int;
}