<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor\Exception;

use App\Twitter\Exception\UnavailableResourceException;

/**
 * @package App\Infrastructure\Twitter\Api\Accessor\Exception
 */
class NotFoundStatusException extends UnavailableResourceException
{
}
