<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Domain\Snapshot\Filter;

use App\NewsReview\Domain\Model\HighlightDto;
use App\NewsReview\Domain\Snapshot\Filter\HighlightNormalizer;
use PHPUnit\Framework\TestCase;

class HighlightNormalizerTest extends TestCase
{
    public function test_bluesky_shape_payload_yields_bsky_url(): void
    {
        $normalizer = new HighlightNormalizer();

        $dto = $normalizer->toDto([
            'screen_name'    => 'lemonde.fr',
            'reposts'        => 12,
            'likes'          => 34,
            'avatar_url'     => 'https://cdn/avatar.jpg',
            'text'           => 'a post',
            'publication_id' => 'at://did:plc:abc/x/post-id-123',
            'publicationDateTime' => '2026-05-01T10:00:00+02:00',
        ]);

        self::assertInstanceOf(HighlightDto::class, $dto);
        self::assertSame('lemonde.fr', $dto->screenName);
        self::assertSame(12, $dto->reposts);
        self::assertSame('https://bsky.app/profile/lemonde.fr/post/post-id-123', $dto->url);
    }

    public function test_missing_avatar_url_yields_null(): void
    {
        $normalizer = new HighlightNormalizer();
        $dto = $normalizer->toDto([
            'screen_name'    => 'lemonde.fr',
            'reposts'        => 0,
            'likes'          => 0,
            'text'           => 'a',
            'publication_id' => 'at://did/x/p',
            'publicationDateTime' => '2026-05-01T10:00:00+02:00',
        ]);

        self::assertNull($dto->avatarUrl);
    }

    public function test_explicit_url_is_preferred_over_derived(): void
    {
        $normalizer = new HighlightNormalizer();
        $dto = $normalizer->toDto([
            'screen_name'    => 'lemonde.fr',
            'publication_id' => 'at://did/x/p',
            'url'            => 'https://override.example/x',
            'text'           => 'a',
            'publicationDateTime' => '2026-05-01T10:00:00+02:00',
        ]);

        self::assertSame('https://override.example/x', $dto->url);
    }
}
