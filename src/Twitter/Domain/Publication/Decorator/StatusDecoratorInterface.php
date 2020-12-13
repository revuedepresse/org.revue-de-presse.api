<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Decorator;

interface StatusDecoratorInterface
{
    public static function decorateStatus(array $retweets): array;
}