<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Collector\Exception;

class RateLimitedException extends SkipCollectException
{
}
