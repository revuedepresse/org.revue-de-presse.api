<?php
declare (strict_types=1);

namespace App\NewsReview\Domain\Routing\Model;

interface RouteInterface
{
    public function hostname(): string;

    public function toArray(): array;
}