<?php
declare(strict_types=1);

namespace App\Accessor\Exception;

use App\Twitter\Exception\UnavailableResourceException;

/**
 * @package App\Accessor\Exception
 */
class NotFoundStatusException extends UnavailableResourceException
{
}
