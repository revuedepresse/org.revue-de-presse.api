<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Exception;

use RuntimeException;

class NoRemainingPublicationException extends RuntimeException
{
}