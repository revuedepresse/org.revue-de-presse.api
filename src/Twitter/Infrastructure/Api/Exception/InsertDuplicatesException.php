<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Exception;

use Exception;
use RuntimeException;

class InsertDuplicatesException extends RuntimeException
{
    /**
     * @param Exception $exception
     */
    public static function throws(Exception $exception): void
    {
        throw new self(
            'Can not insert duplicates into the database',
            0,
            $exception
        );
    }
}