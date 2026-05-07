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
        SymfonySetList::SYMFONY_70,
        SymfonySetList::SYMFONY_71,
        SymfonySetList::SYMFONY_72,
        SymfonySetList::SYMFONY_73,
        SymfonySetList::SYMFONY_74,
    ])
    ->withPhpSets()
    ->withTypeCoverageLevel(0);
