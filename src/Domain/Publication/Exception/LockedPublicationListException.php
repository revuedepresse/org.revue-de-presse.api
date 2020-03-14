<?php
declare(strict_types=1);

namespace App\Domain\Publication\Exception;

use App\Domain\Publication\PublicationListInterface;
use RuntimeException;

class LockedPublicationListException extends RuntimeException
{
    public static function throws(
        string $message,
        PublicationListInterface $publicationList
    ): void {
        throw new self(sprintf($message, $publicationList->getId()));
    }
}