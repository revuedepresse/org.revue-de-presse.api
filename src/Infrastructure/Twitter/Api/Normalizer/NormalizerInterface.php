<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Api\Normalizer;

use App\Domain\Publication\TaggedStatus;

interface NormalizerInterface
{
    public static function normalizeStatusProperties(
        \stdClass $properties,
        \Closure $onFinish = null
    ): TaggedStatus;
}