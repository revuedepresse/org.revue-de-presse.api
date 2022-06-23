<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Console\Exception;

use Exception;

class InterruptedConsoleCommandException extends Exception
{
    public static function throws(): void
    {
        throw new self('This command has been interrupted.');
    }
}