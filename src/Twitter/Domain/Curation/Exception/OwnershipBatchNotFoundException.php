<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Curation\Exception;

use Exception;

class OwnershipBatchNotFoundException extends Exception
{
    public static function throws(string $screenName): void
    {
        throw new self(
            sprintf(
                'Could not find ownership batch for member having screen name "%s"',
                $screenName
            )
        );
    }
}