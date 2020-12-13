<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Exception;

use App\Twitter\Domain\Publication\PublishersListInterface;
use RuntimeException;

class LockedPublishersListException extends RuntimeException
{
    public static function throws(
        string $message,
        PublishersListInterface $publishersList
    ): void {
        throw new self(sprintf($message, $publishersList->getId()));
    }
}