<?php
declare(strict_types=1);

namespace App\Infrastructure\Log;

use App\Domain\Status\StatusInterface;

interface StatusLoggerInterface
{
    public function logStatus(StatusInterface $status): void;
}