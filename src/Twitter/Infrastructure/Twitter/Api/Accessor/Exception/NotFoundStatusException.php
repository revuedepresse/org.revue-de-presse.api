<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception;

use App\Twitter\Infrastructure\Exception\UnavailableResourceException;

/**
 * @package App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception
 */
class NotFoundStatusException extends UnavailableResourceException
{
}
