<?php
declare (strict_types=1);

namespace App\NewsReview\Domain\Exception;

use App\NewsReview\Domain\Routing\Model\PublishersListInterface;
use Exception;

class PublishersListRouteAlreadyExposedException extends Exception
{
    public static function throws(PublishersListInterface $publishersList): void
    {
        throw new self(
            sprintf(
            'A route has already been exposed for publishers list "%s".',
                $publishersList->name()
            )
        );
    }
}