<?php
declare(strict_types=1);

namespace App\Infrastructure\Log;

use App\Api\Entity\StatusInterface;

interface StatusLoggerInterface
{
    public function logStatus(StatusInterface $status): void;
}