<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\Entity;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\InvalidStatusTransition;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class SubscriberTest extends TestCase
{
    private function makeToken(): OpaqueToken
    {
        return OpaqueToken::fromRawBytes(random_bytes(32));
    }

    private function enrolNow(): Subscriber
    {
        return Subscriber::enrol(
            id: new Ulid(),
            email: EmailAddress::fromString('alice@example.com'),
            confirmToken: $this->makeToken(),
            confirmExpiresAt: new \DateTimeImmutable('+7 days'),
            unsubToken: $this->makeToken(),
            enrolledBy: 'rdp-api',
            now: new \DateTimeImmutable('2026-05-23T05:30:00Z'),
        );
    }

    public function test_enrol_creates_pending_row(): void
    {
        $s = $this->enrolNow();
        self::assertSame(SubscriberStatus::Pending, $s->status());
        self::assertNotNull($s->confirmToken());
        self::assertSame('rdp-api', $s->enrolledBy());
    }

    public function test_confirm_pending_with_valid_token_flips_to_active(): void
    {
        $s = $this->enrolNow();
        $token = $s->confirmToken();
        self::assertNotNull($token);
        $s->confirm($token, new \DateTimeImmutable('2026-05-23T06:00:00Z'));
        self::assertSame(SubscriberStatus::Active, $s->status());
        self::assertNull($s->confirmToken());
        self::assertNotNull($s->confirmedAt());
    }

    public function test_confirm_rejects_wrong_token(): void
    {
        $s = $this->enrolNow();
        $this->expectException(InvalidStatusTransition::class);
        $s->confirm($this->makeToken(), new \DateTimeImmutable('2026-05-23T06:00:00Z'));
    }

    public function test_confirm_rejects_expired_token(): void
    {
        $s = Subscriber::enrol(
            id: new Ulid(),
            email: EmailAddress::fromString('alice@example.com'),
            confirmToken: $this->makeToken(),
            confirmExpiresAt: new \DateTimeImmutable('2026-05-23T05:30:00Z'),
            unsubToken: $this->makeToken(),
            enrolledBy: 'rdp-api',
            now: new \DateTimeImmutable('2026-05-23T05:30:00Z'),
        );
        $this->expectException(InvalidStatusTransition::class);
        $s->confirm($s->confirmToken(), new \DateTimeImmutable('2026-06-23T00:00:00Z'));
    }

    public function test_confirm_on_already_active_is_noop_idempotent(): void
    {
        $s = $this->enrolNow();
        $t = $s->confirmToken();
        $s->confirm($t, new \DateTimeImmutable('2026-05-23T06:00:00Z'));
        // second confirm should not throw — already-active is idempotent
        $s->confirm($t, new \DateTimeImmutable('2026-05-23T07:00:00Z'));
        self::assertSame(SubscriberStatus::Active, $s->status());
    }

    public function test_unsubscribe_active_subscriber_rotates_token(): void
    {
        $s = $this->enrolNow();
        $s->confirm($s->confirmToken(), new \DateTimeImmutable('2026-05-23T06:00:00Z'));
        $oldUnsub = $s->unsubToken();
        $newUnsub = $this->makeToken();
        $s->unsubscribe($oldUnsub, $newUnsub, new \DateTimeImmutable('2026-05-24T00:00:00Z'));
        self::assertSame(SubscriberStatus::Unsubscribed, $s->status());
        self::assertTrue($s->unsubToken()->equals($newUnsub));
        self::assertFalse($s->unsubToken()->equals($oldUnsub));
        self::assertNotNull($s->unsubscribedAt());
    }

    public function test_unsubscribe_rejects_wrong_token(): void
    {
        $s = $this->enrolNow();
        $s->confirm($s->confirmToken(), new \DateTimeImmutable('2026-05-23T06:00:00Z'));
        $this->expectException(InvalidStatusTransition::class);
        $s->unsubscribe($this->makeToken(), $this->makeToken(), new \DateTimeImmutable());
    }

    public function test_reenrol_flips_unsubscribed_back_to_pending(): void
    {
        $s = $this->enrolNow();
        $s->confirm($s->confirmToken(), new \DateTimeImmutable('2026-05-23T06:00:00Z'));
        $s->unsubscribe($s->unsubToken(), $this->makeToken(), new \DateTimeImmutable('2026-05-24'));

        $newConfirm = $this->makeToken();
        $newUnsub = $this->makeToken();
        $s->reenrol(
            confirmToken: $newConfirm,
            confirmExpiresAt: new \DateTimeImmutable('2026-06-01'),
            unsubToken: $newUnsub,
            enrolledBy: 'rdp-api',
            now: new \DateTimeImmutable('2026-05-25'),
        );
        self::assertSame(SubscriberStatus::Pending, $s->status());
        self::assertTrue($s->confirmToken()->equals($newConfirm));
        self::assertNull($s->confirmedAt());
        self::assertNull($s->unsubscribedAt());
    }

    public function test_mark_sent_updates_last_sent_at(): void
    {
        $s = $this->enrolNow();
        $s->confirm($s->confirmToken(), new \DateTimeImmutable('2026-05-23T06:00:00Z'));
        $s->markSent(new \DateTimeImmutable('2026-05-24T05:30:00Z'));
        self::assertSame('2026-05-24T05:30:00+00:00', $s->lastSentAt()?->format(\DateTimeInterface::ATOM));
    }
}
