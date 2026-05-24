<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

final class HighlightView
{
    public function __construct(
        public readonly string $rank,
        public readonly string $screen_name,
        public readonly ?string $avatar_url,
        public readonly string $text,
        public readonly string $date_fr,
        public readonly int $reposts,
        public readonly int $likes,
        public readonly string $url,
    ) {}
}
