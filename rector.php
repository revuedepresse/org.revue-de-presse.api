<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/src/Bluesky/Resources',
        __DIR__ . '/tests/Resources',
        __DIR__ . '/src/Twitter/Infrastructure/Security/Authentication/TokenAuthenticator.php',
    ])
    ->withSets([
        SymfonySetList::SYMFONY_64,
    ])
    ->withPhpSets()
    ->withTypeCoverageLevel(0);
