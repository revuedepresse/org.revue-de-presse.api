<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\Resource;

use ApiPlatform\Metadata\ApiProperty;

final readonly class HighlightDto
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $publicationId,
        public string $screenName,
        public ?string $avatarUrl,
        public string $text,
        public int $reposts,
        public int $likes,
        public int $replies,
        public \DateTimeImmutable $date,
        public string $url,
    ) {
    }
}
