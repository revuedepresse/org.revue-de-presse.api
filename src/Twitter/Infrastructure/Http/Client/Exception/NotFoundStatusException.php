<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client\Exception;

use App\Twitter\Infrastructure\Exception\UnavailableResourceException;

/**
 * @package App\Twitter\Infrastructure\Http\Client\Exception
 */
class NotFoundStatusException extends UnavailableResourceException
{
}
