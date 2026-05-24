<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

use App\Newsletter\Domain\Repository\SubscriberRepository;
use App\Newsletter\Domain\ValueObject\InvalidStatusTransition;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;
use Psr\Clock\ClockInterface;

final class SubscriberConfirmer
{
    public function __construct(
        private readonly SubscriberRepository $repository,
        private readonly ClockInterface $clock,
    ) {}

    public function confirm(OpaqueToken $token): ConfirmationResult
    {
        $sub = $this->repository->findByConfirmToken($token);
        if ($sub === null) {
            return ConfirmationResult::InvalidOrExpired;
        }
        if ($sub->status() === SubscriberStatus::Active) {
            return ConfirmationResult::AlreadyActive;
        }
        try {
            $sub->confirm($token, $this->clock->now());
        } catch (InvalidStatusTransition) {
            return ConfirmationResult::InvalidOrExpired;
        }
        $this->repository->save($sub);
        return ConfirmationResult::Confirmed;
    }
}
