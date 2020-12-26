<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Normalizer;

use App\Twitter\Infrastructure\Publication\Dto\TaggedStatus;

interface NormalizerInterface
{
    public static function normalizeStatusProperties(
        \stdClass $properties,
        \Closure $onFinish = null
    ): TaggedStatus;
}