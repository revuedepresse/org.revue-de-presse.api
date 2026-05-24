<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\Repository;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class InMemorySubscriberRepositoryTest extends TestCase
{
    public function test_round_trips_a_saved_subscriber(): void
    {
        $repo = new InMemorySubscriberRepository();
        $email = EmailAddress::fromString('alice@example.com');
        $s = Subscriber::enrol(
            new Ulid(),
            $email,
            OpaqueToken::fromRawBytes(random_bytes(32)),
            new \DateTimeImmutable('+7 days'),
            OpaqueToken::fromRawBytes(random_bytes(32)),
            'rdp-api',
            new \DateTimeImmutable(),
        );
        $repo->save($s);
        self::assertSame($s, $repo->findByEmailHash($email->hash()));
    }
}
