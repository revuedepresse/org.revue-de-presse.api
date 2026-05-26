<?php
declare(strict_types=1);

namespace App\Tests\Chat\Domain\Entity;

use App\Chat\Domain\Entity\Conversation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ConversationTest extends TestCase
{
    public function testConstructorSetsDidAndTimestamps(): void
    {
        $now = new \DateTimeImmutable('2026-05-26T10:00:00+00:00');
        $conv = new Conversation('did:plc:abc', null, $now);
        self::assertSame('did:plc:abc', $conv->blueskyDid());
        self::assertSame($now, $conv->createdAt());
        self::assertSame($now, $conv->lastTurnAt());
        self::assertInstanceOf(Uuid::class, $conv->id());
    }

    public function testConstructorAcceptsExplicitId(): void
    {
        $id = Uuid::v7();
        $conv = new Conversation('did:plc:abc', $id);
        self::assertSame($id, $conv->id());
    }

    public function testTouchAdvancesLastTurnAtOnly(): void
    {
        $createdAt = new \DateTimeImmutable('2026-05-26T10:00:00+00:00');
        $later = new \DateTimeImmutable('2026-05-26T11:30:00+00:00');
        $conv = new Conversation('did:plc:abc', null, $createdAt);
        $conv->touch($later);
        self::assertSame($createdAt, $conv->createdAt());
        self::assertSame($later, $conv->lastTurnAt());
    }
}
