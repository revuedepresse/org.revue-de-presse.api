<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
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
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ])
    ->withPhpSets()
    ->withTypeCoverageLevel(0);
