<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Repository\SubscriberRepository;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final class SubscriberEnroller
{
    public function __construct(
        private readonly SubscriberRepository $repository,
        private readonly TokenGenerator $tokens,
        private readonly ClockInterface $clock,
        private readonly int $confirmTtlHours,
        private readonly string $actingUser,
    ) {}

    public function enrol(EmailAddress $email): EnrolmentOutcome
    {
        $now = $this->clock->now();
        $existing = $this->repository->findByEmailHash($email->hash());

        if ($existing === null) {
            $confirm = $this->tokens->generate();
            $unsub = $this->tokens->generate();
            $sub = Subscriber::enrol(
                id: new Ulid(),
                email: $email,
                confirmToken: $confirm,
                confirmExpiresAt: $now->modify(sprintf('+%d hours', $this->confirmTtlHours)),
                unsubToken: $unsub,
                enrolledBy: $this->actingUser,
                now: $now,
            );
            $this->repository->save($sub);
            return EnrolmentOutcome::created($confirm);
        }

        return match ($existing->status()) {
            SubscriberStatus::Active => EnrolmentOutcome::alreadyActive(),
            SubscriberStatus::Pending => EnrolmentOutcome::resent($existing->confirmToken()),
            SubscriberStatus::Unsubscribed => $this->reenrol($existing, $now),
        };
    }

    private function reenrol(Subscriber $sub, \DateTimeImmutable $now): EnrolmentOutcome
    {
        $confirm = $this->tokens->generate();
        $unsub = $this->tokens->generate();
        $sub->reenrol(
            confirmToken: $confirm,
            confirmExpiresAt: $now->modify(sprintf('+%d hours', $this->confirmTtlHours)),
            unsubToken: $unsub,
            enrolledBy: $this->actingUser,
            now: $now,
        );
        $this->repository->save($sub);
        return EnrolmentOutcome::reenrolled($confirm);
    }
}
