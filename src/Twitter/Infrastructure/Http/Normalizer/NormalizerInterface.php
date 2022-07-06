<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Normalizer;

use App\Twitter\Infrastructure\Publication\Dto\TaggedTweet;

interface NormalizerInterface
{
    public static function normalizeStatusProperties(
        \stdClass $properties,
        \Closure $onFinish = null
    ): TaggedTweet;
}