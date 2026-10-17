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

    public function enrol(EmailAddress $email, ?string $enrolledBy = null): EnrolmentOutcome
    {
        $now = $this->clock->now();
        $enrolledBy ??= $this->actingUser;
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
                enrolledBy: $enrolledBy,
                now: $now,
            );
            $this->repository->save($sub);
            return EnrolmentOutcome::created($confirm);
        }

        return match ($existing->status()) {
            SubscriberStatus::Active => EnrolmentOutcome::alreadyActive(),
            SubscriberStatus::Pending => $this->resendPending($existing),
            SubscriberStatus::Unsubscribed => $this->reenrol($existing, $now, $enrolledBy),
        };
    }

    private function resendPending(Subscriber $existing): EnrolmentOutcome
    {
        $token = $existing->confirmToken();
        \assert($token !== null, 'Pending subscriber must have a confirm token');
        return EnrolmentOutcome::resent($token);
    }

    private function reenrol(Subscriber $sub, \DateTimeImmutable $now, string $enrolledBy): EnrolmentOutcome
    {
        $confirm = $this->tokens->generate();
        $unsub = $this->tokens->generate();
        $sub->reenrol(
            confirmToken: $confirm,
            confirmExpiresAt: $now->modify(sprintf('+%d hours', $this->confirmTtlHours)),
            unsubToken: $unsub,
            enrolledBy: $enrolledBy,
            now: $now,
        );
        $this->repository->save($sub);
        return EnrolmentOutcome::reenrolled($confirm);
    }
}
