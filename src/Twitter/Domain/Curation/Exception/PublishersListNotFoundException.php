<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Curation\Exception;

use Exception;

class PublishersListNotFoundException extends Exception
{
    public static function throws(string $listId): void
    {
        throw new self(
            sprintf(
                'Could not find Twitter list having id "%s"',
                $listId
            )
        );
    }
}