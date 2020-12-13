<?php
declare(strict_types=1);

namespace App\Domain\Publication\Decorator;

interface StatusDecoratorInterface
{
    public static function decorateStatus(array $retweets): array;
}