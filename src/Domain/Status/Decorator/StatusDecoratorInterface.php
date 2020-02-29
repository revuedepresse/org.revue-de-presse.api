<?php
declare(strict_types=1);

namespace App\Domain\Status\Decorator;

interface StatusDecoratorInterface
{
    public static function decorateStatus(array $retweets): array;
}