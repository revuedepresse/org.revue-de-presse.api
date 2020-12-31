<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Accessor\Exception;

use App\Twitter\Infrastructure\Exception\UnavailableResourceException;

/**
 * @package App\Twitter\Infrastructure\Api\Accessor\Exception
 */
class NotFoundStatusException extends UnavailableResourceException
{
}
