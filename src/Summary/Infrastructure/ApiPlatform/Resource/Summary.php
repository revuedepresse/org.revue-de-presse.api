<?php
declare(strict_types=1);

namespace App\Summary\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Summary\Infrastructure\ApiPlatform\State\SummaryProvider;

/**
 * GET /api/days/{date}/summary — one day's pre-computed thematic synthesis.
 *
 * Response shape:
 *   { "date": "2026-05-26", "markdown": "## Politique\n..." }
 *
 * 404 when the day has no summary file (corpus gap or backfill incomplete).
 *
 * Public read (no auth) — summaries are derived from public Bluesky data
 * and there's no value in restricting them.
 */
#[ApiResource(
    shortName: 'Summary',
    operations: [
        new Get(
            uriTemplate: '/days/{date}/summary',
            // Pre-computed content is immutable for the day; cache hard.
            // shared_max_age lets the reverse proxy / CDN hold it forever
            // until invalidated. We never PUT this resource — re-runs of
            // the generator with --force overwrite on disk, but the URL is
            // versionless. If you re-backfill with a new prompt, invalidate
            // the cache via a HTTP PURGE or just bump shared_max_age via
            // a deploy.
            cacheHeaders: [
                'max_age'        => 3600,
                'shared_max_age' => 86400,
                'public'         => true,
            ],
            provider: SummaryProvider::class,
        ),
    ],
)]
final class Summary
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $date,
        public string $markdown,
    ) {
    }
}
