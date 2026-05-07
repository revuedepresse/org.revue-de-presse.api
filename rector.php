<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/src/Bluesky/Resources',
        __DIR__ . '/tests/Resources',
    ])
    ->withPhpSets()
    ->withTypeCoverageLevel(0);
