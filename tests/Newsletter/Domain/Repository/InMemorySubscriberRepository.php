<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\Repository;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Repository\SubscriberRepository;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;

final class InMemorySubscriberRepository implements SubscriberRepository
{
    /** @var array<string, Subscriber> */
    private array $byId = [];

    public function save(Subscriber $subscriber): void
    {
        $this->byId[(string) $subscriber->id()] = $subscriber;
    }

    public function findByEmailHash(string $emailHash): ?Subscriber
    {
        foreach ($this->byId as $s) {
            if (hash_equals($s->emailHash(), $emailHash)) {
                return $s;
            }
        }
        return null;
    }

    public function findByConfirmToken(OpaqueToken $token): ?Subscriber
    {
        foreach ($this->byId as $s) {
            $ct = $s->confirmToken();
            if ($ct !== null && $ct->equals($token)) {
                return $s;
            }
        }
        return null;
    }

    public function findByUnsubToken(OpaqueToken $token): ?Subscriber
    {
        foreach ($this->byId as $s) {
            if ($s->unsubToken()->equals($token)) {
                return $s;
            }
        }
        return null;
    }

    public function iterateActive(int $batchSize = 200): iterable
    {
        yield from $this->iterateByStatus(SubscriberStatus::Active->value, $batchSize);
    }

    public function iterateByStatus(string $status, int $batchSize = 200): iterable
    {
        foreach ($this->byId as $s) {
            if ($s->status()->value === $status) {
                yield $s;
            }
        }
    }
}
