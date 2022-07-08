<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Normalizer;

use App\Twitter\Domain\Publication\TaggedStatus;

interface NormalizerInterface
{
    public static function normalizeStatusProperties(
        \stdClass $properties,
        \Closure $onFinish = null
    ): TaggedStatus;
}
