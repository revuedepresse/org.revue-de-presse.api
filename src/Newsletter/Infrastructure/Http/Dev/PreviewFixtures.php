<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Http\Dev;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Service\HighlightView;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use Symfony\Component\Uid\Ulid;

final class PreviewFixtures
{
    /** @return HighlightView[] */
    public static function highlights(int $count = 10, \DateTimeImmutable $date = new \DateTimeImmutable('2026-05-23')): array
    {
        $samples = ['lemonde', 'liberation', 'mediapart', 'franceinter', 'lefigaro', 'lemonde_pol', 'lemonde_eco', 'le_telegramme', 'leparisien', 'mediapart_inv'];
        $views = [];
        $count = max(1, min(10, $count));
        for ($i = 0; $i < $count; $i++) {
            $views[] = new HighlightView(
                rank: sprintf('%02d', $i + 1),
                screen_name: $samples[$i],
                avatar_url: null,
                text: 'Sample headline number ' . ($i + 1) . ' for the daily report preview.',
                date_fr: \IntlDateFormatter::create('fr_FR', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE)->format($date),
                reposts: 100 + $i * 7,
                likes: 200 + $i * 13,
                url: 'https://bsky.app/profile/' . $samples[$i] . '/post/preview-' . ($i + 1),
            );
        }
        return $views;
    }

    public static function sampleSubscriber(): Subscriber
    {
        return Subscriber::enrol(
            new Ulid(),
            EmailAddress::fromString('preview@example.com'),
            OpaqueToken::fromRawBytes(str_repeat("\x01", 32)),
            new \DateTimeImmutable('+7 days'),
            OpaqueToken::fromRawBytes(str_repeat("\x02", 32)),
            'preview',
            new \DateTimeImmutable(),
        );
    }
}
