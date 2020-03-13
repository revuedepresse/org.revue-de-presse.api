<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Accessor\Exception;

use App\Twitter\Infrastructure\Exception\UnavailableResourceException;

/**
 * @package App\Twitter\Infrastructure\Http\Accessor\Exception
 */
class NotFoundStatusException extends UnavailableResourceException
{
}
