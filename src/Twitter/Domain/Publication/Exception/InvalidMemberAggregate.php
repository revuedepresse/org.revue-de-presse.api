<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Exception;

use function sprintf;

class InvalidMemberAggregate extends \Exception
{
    public static function guardAgainstInvalidUsername(
        string $username
    ): void {
        throw new self(
            sprintf(
                'Could not find aggregate for member having username "%s"',
                $username
            )
        );
    }
}
