<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\Service;

use App\Newsletter\Domain\Service\EnrolmentOutcome;
use App\Newsletter\Domain\Service\EnrolmentOutcomeKind;
use App\Newsletter\Domain\Service\SubscriberEnroller;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;
use App\Tests\Newsletter\Domain\Repository\InMemorySubscriberRepository;
use PHPUnit\Framework\TestCase;

final class SubscriberEnrollerTest extends TestCase
{
    private function makeEnroller(InMemorySubscriberRepository $repo, \DateTimeImmutable $now): SubscriberEnroller
    {
        return new SubscriberEnroller(
            repository: $repo,
            tokens: new PredictableTokenGenerator(),
            clock: new FrozenClock($now),
            confirmTtlHours: 168,
            actingUser: 'rdp-api',
        );
    }

    public function test_missing_row_inserts_pending(): void
    {
        $repo = new InMemorySubscriberRepository();
        $enroller = $this->makeEnroller($repo, new \DateTimeImmutable('2026-05-23T05:30:00Z'));
        $outcome = $enroller->enrol(EmailAddress::fromString('alice@example.com'));

        self::assertSame(EnrolmentOutcomeKind::Created, $outcome->result);
        $sub = $repo->findByEmailHash(EmailAddress::fromString('alice@example.com')->hash());
        self::assertNotNull($sub);
        self::assertSame(SubscriberStatus::Pending, $sub->status());
        self::assertTrue($outcome->confirmToken->equals($sub->confirmToken()));
    }

    public function test_pending_row_resends_with_same_token(): void
    {
        $repo = new InMemorySubscriberRepository();
        $enroller = $this->makeEnroller($repo, new \DateTimeImmutable('2026-05-23T05:30:00Z'));
        $first = $enroller->enrol(EmailAddress::fromString('alice@example.com'));
        $second = $enroller->enrol(EmailAddress::fromString('alice@example.com'));

        self::assertSame(EnrolmentOutcomeKind::ResentConfirmation, $second->result);
        self::assertTrue($second->confirmToken->equals($first->confirmToken));
    }

    public function test_active_row_returns_already_active_noop(): void
    {
        $repo = new InMemorySubscriberRepository();
        $now = new \DateTimeImmutable('2026-05-23T05:30:00Z');
        $enroller = $this->makeEnroller($repo, $now);
        $first = $enroller->enrol(EmailAddress::fromString('alice@example.com'));
        $sub = $repo->findByEmailHash(EmailAddress::fromString('alice@example.com')->hash());
        $sub->confirm($first->confirmToken, $now->modify('+1 hour'));
        $repo->save($sub);

        $second = $enroller->enrol(EmailAddress::fromString('alice@example.com'));
        self::assertSame(EnrolmentOutcomeKind::AlreadyActive, $second->result);
        self::assertNull($second->confirmToken);
    }

    public function test_unsubscribed_row_flips_back_to_pending_with_fresh_tokens(): void
    {
        $repo = new InMemorySubscriberRepository();
        $now = new \DateTimeImmutable('2026-05-23T05:30:00Z');
        $enroller = $this->makeEnroller($repo, $now);
        $first = $enroller->enrol(EmailAddress::fromString('alice@example.com'));
        $sub = $repo->findByEmailHash(EmailAddress::fromString('alice@example.com')->hash());
        $sub->confirm($first->confirmToken, $now->modify('+1 hour'));
        // unsubscribe with current unsub token
        $sub->unsubscribe(
            $sub->unsubToken(),
            OpaqueToken::fromRawBytes(random_bytes(32)),
            $now->modify('+1 day'),
        );
        $repo->save($sub);

        $third = $enroller->enrol(EmailAddress::fromString('alice@example.com'));
        self::assertSame(EnrolmentOutcomeKind::Reenrolled, $third->result);
        $sub2 = $repo->findByEmailHash(EmailAddress::fromString('alice@example.com')->hash());
        self::assertSame(SubscriberStatus::Pending, $sub2->status());
    }
}
