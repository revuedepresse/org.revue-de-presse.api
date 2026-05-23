<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\Service;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Service\SubscriberUnsubscriber;
use App\Newsletter\Domain\Service\UnsubscribeResult;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Tests\Newsletter\Domain\Repository\InMemorySubscriberRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class SubscriberUnsubscriberTest extends TestCase
{
    public function test_unsubscribes_and_rotates_token(): void
    {
        $repo = new InMemorySubscriberRepository();
        $unsubToken = OpaqueToken::fromRawBytes(random_bytes(32));
        $confirmToken = OpaqueToken::fromRawBytes(random_bytes(32));
        $sub = Subscriber::enrol(
            new Ulid(),
            EmailAddress::fromString('alice@example.com'),
            $confirmToken,
            new \DateTimeImmutable('+7 days'),
            $unsubToken,
            'rdp-api',
            new \DateTimeImmutable('2026-05-23T05:30:00Z'),
        );
        $sub->confirm($confirmToken, new \DateTimeImmutable('2026-05-23T06:00:00Z'));
        $repo->save($sub);

        $service = new SubscriberUnsubscriber($repo, new PredictableTokenGenerator(), new FrozenClock(new \DateTimeImmutable('2026-05-24')));
        self::assertSame(UnsubscribeResult::Unsubscribed, $service->unsubscribe($unsubToken));

        $fresh = $repo->findByEmailHash(EmailAddress::fromString('alice@example.com')->hash());
        self::assertFalse($fresh->unsubToken()->equals($unsubToken), 'token should rotate');
    }

    public function test_unknown_token_returns_invalid(): void
    {
        $repo = new InMemorySubscriberRepository();
        $service = new SubscriberUnsubscriber($repo, new PredictableTokenGenerator(), new FrozenClock(new \DateTimeImmutable()));
        self::assertSame(UnsubscribeResult::InvalidToken, $service->unsubscribe(OpaqueToken::fromRawBytes(random_bytes(32))));
    }
}
