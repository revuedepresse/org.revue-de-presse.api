<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Exception;

class RateLimitedException extends SkipCollectException
{
}
