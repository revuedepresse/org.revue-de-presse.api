<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\Snapshot\Filter;

use App\NewsReview\Domain\Model\HighlightDto;

final class HighlightNormalizer
{
    public function toDto(array $raw): HighlightDto
    {
        $publicationId = (string) ($raw['publication_id'] ?? '');
        $screenName    = (string) ($raw['screen_name'] ?? '');
        $url           = $this->buildUrl($publicationId, $screenName, $raw['url'] ?? null);
        $date          = $this->parseDate((string) ($raw['publicationDateTime'] ?? $raw['date'] ?? 'now'));

        return new HighlightDto(
            publicationId: $publicationId,
            screenName:    $screenName,
            avatarUrl:     isset($raw['avatar_url']) ? (string) $raw['avatar_url'] : null,
            text:          (string) ($raw['text'] ?? ''),
            reposts:       (int) ($raw['reposts'] ?? 0),
            likes:         (int) ($raw['likes'] ?? 0),
            replies:       (int) ($raw['replies'] ?? 0),
            date:          $date,
            url:           $url,
        );
    }

    private function buildUrl(string $publicationId, string $screenName, mixed $explicit): string
    {
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        if (str_starts_with($publicationId, 'at://')) {
            $parts = explode('/', $publicationId);
            $tail  = end($parts);

            return 'https://bsky.app/profile/' . $screenName . '/post/' . $tail;
        }

        return $publicationId;
    }

    private function parseDate(string $raw): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($raw);
        } catch (\Throwable) {
            return new \DateTimeImmutable();
        }
    }
}
