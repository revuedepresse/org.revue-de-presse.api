<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Infrastructure\Security\AccessTokenRecord;
use PHPUnit\Framework\TestCase;

class AccessTokenRecordTest extends TestCase
{
    public function test_carries_member_id_issued_at_expires_at(): void
    {
        $issued = new \DateTimeImmutable('2026-05-17T10:00:00Z');
        $expires = new \DateTimeImmutable('2026-05-17T10:15:00Z');
        $record = new AccessTokenRecord('42', $issued, $expires);

        self::assertSame('42', $record->memberId);
        self::assertSame($issued, $record->issuedAt);
        self::assertSame($expires, $record->expiresAt);
    }

    public function test_is_expired_returns_true_when_expires_at_in_past(): void
    {
        $past = new \DateTimeImmutable('-1 minute');
        $record = new AccessTokenRecord('42', new \DateTimeImmutable('-15 minutes'), $past);

        self::assertTrue($record->isExpired());
    }

    public function test_is_expired_returns_false_when_expires_at_in_future(): void
    {
        $future = new \DateTimeImmutable('+5 minutes');
        $record = new AccessTokenRecord('42', new \DateTimeImmutable('-10 minutes'), $future);

        self::assertFalse($record->isExpired());
    }
}
