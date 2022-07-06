<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Api;

interface UnavailableResourceInterface extends TwitterErrorAwareInterface
{
    public function getType(): int;
}