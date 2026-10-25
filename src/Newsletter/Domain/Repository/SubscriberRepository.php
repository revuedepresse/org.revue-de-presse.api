<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Repository;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\ValueObject\Edition;
use App\Newsletter\Domain\ValueObject\OpaqueToken;

interface SubscriberRepository
{
    public function save(Subscriber $subscriber): void;

    public function findByEmailHash(string $emailHash, Edition $edition = Edition::FrenchMedia): ?Subscriber;

    public function findByConfirmToken(OpaqueToken $token): ?Subscriber;

    public function findByUnsubToken(OpaqueToken $token): ?Subscriber;

    /**
     * Active subscriptions to one edition, or to every edition when none is given.
     *
     * @return iterable<Subscriber>
     */
    public function iterateActive(int $batchSize = 200, ?Edition $edition = null): iterable;

    /**
     * @return iterable<Subscriber>
     */
    public function iterateByStatus(string $status, int $batchSize = 200): iterable;

    /**
     * Delete every subscriber row. Returns the number of rows that existed
     * before truncation.
     */
    public function truncate(): int;
}
