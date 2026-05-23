<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

use App\Newsletter\Domain\Repository\SubscriberRepository;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;
use Psr\Clock\ClockInterface;

final class SubscriberUnsubscriber
{
    public function __construct(
        private readonly SubscriberRepository $repository,
        private readonly TokenGenerator $tokens,
        private readonly ClockInterface $clock,
    ) {}

    public function unsubscribe(OpaqueToken $token): UnsubscribeResult
    {
        $sub = $this->repository->findByUnsubToken($token);
        if ($sub === null) {
            return UnsubscribeResult::InvalidToken;
        }
        if ($sub->status() === SubscriberStatus::Unsubscribed) {
            return UnsubscribeResult::AlreadyUnsubscribed;
        }
        $sub->unsubscribe($token, $this->tokens->generate(), $this->clock->now());
        $this->repository->save($sub);
        return UnsubscribeResult::Unsubscribed;
    }
}
