<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\Service;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Service\ConfirmationResult;
use App\Newsletter\Domain\Service\SubscriberConfirmer;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Tests\Newsletter\Domain\Repository\InMemorySubscriberRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class SubscriberConfirmerTest extends TestCase
{
    public function test_valid_token_confirms_pending_row(): void
    {
        $repo = new InMemorySubscriberRepository();
        $confirm = OpaqueToken::fromRawBytes(random_bytes(32));
        $sub = Subscriber::enrol(
            new Ulid(),
            EmailAddress::fromString('alice@example.com'),
            $confirm,
            new \DateTimeImmutable('+7 days'),
            OpaqueToken::fromRawBytes(random_bytes(32)),
            'rdp-api',
            new \DateTimeImmutable('2026-05-23T05:30:00Z'),
        );
        $repo->save($sub);

        $confirmer = new SubscriberConfirmer($repo, new FrozenClock(new \DateTimeImmutable('2026-05-23T06:00:00Z')));
        self::assertSame(ConfirmationResult::Confirmed, $confirmer->confirm($confirm));
    }

    public function test_unknown_token_returns_invalid(): void
    {
        $repo = new InMemorySubscriberRepository();
        $confirmer = new SubscriberConfirmer($repo, new FrozenClock(new \DateTimeImmutable()));
        self::assertSame(
            ConfirmationResult::InvalidOrExpired,
            $confirmer->confirm(OpaqueToken::fromRawBytes(random_bytes(32))),
        );
    }
}
