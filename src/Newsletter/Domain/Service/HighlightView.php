<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

final class HighlightView
{
    public function __construct(
        public readonly string $rank,
        public readonly string $screenName,
        public readonly ?string $avatarUrl,
        public readonly string $text,
        public readonly string $dateFr,
        public readonly int $reposts,
        public readonly int $likes,
        public readonly string $url,
    ) {}
}
