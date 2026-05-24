<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Highlights;

use App\Newsletter\Domain\Service\DailyHighlightsSource;
use App\Newsletter\Domain\Service\HighlightView;
use App\Newsletter\Infrastructure\Text\TextCleaner;
use App\NewsReview\Domain\Snapshot\SnapshotReader;

final class SnapshotHighlightsFetcher implements DailyHighlightsSource
{
    public function __construct(private readonly SnapshotReader $snapshots)
    {}

    public function fetchTop10(\DateTimeImmutable $date): array
    {
        $raw = $this->snapshots->read($date->format('Y-m-d'));
        $views = [];
        foreach (array_slice($raw, 0, 10) as $i => $row) {
            $views[] = new HighlightView(
                rank: sprintf('%02d', $i + 1),
                screen_name: (string) ($row['screen_name'] ?? $row['screenName'] ?? ''),
                avatar_url: $row['avatar_url'] ?? $row['avatarUrl'] ?? null,
                text: $this->cleanText((string) ($row['text'] ?? '')),
                date_fr: $this->formatDateFr($date),
                reposts: (int) ($row['reposts'] ?? 0),
                likes: (int) ($row['likes'] ?? 0),
                url: (string) ($row['url'] ?? ''),
            );
        }
        return $views;
    }

    private function formatDateFr(\DateTimeImmutable $date): string
    {
        $fmt = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
        return $fmt->format($date);
    }

    private function cleanText(string $raw): string
    {
        return TextCleaner::clean($raw);
    }
}
