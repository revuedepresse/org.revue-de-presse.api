<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\Database\Connection;

interface ConnectionAwareInterface
{
    public function reconnect(): void;
}