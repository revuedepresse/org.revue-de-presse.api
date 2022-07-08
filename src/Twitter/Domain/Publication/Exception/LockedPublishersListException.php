<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Exception;

use App\Twitter\Domain\Publication\MembersListInterface;
use RuntimeException;

class LockedPublishersListException extends RuntimeException
{
    public static function throws(
        string $message,
        MembersListInterface $publishersList
    ): void {
        throw new self(sprintf($message, $publishersList->getId()));
    }
}
