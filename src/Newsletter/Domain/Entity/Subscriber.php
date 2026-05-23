<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Entity;

use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\InvalidStatusTransition;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'newsletter_subscribers')]
#[ORM\Index(columns: ['status'], name: 'idx_newsletter_subscribers_status')]
class Subscriber
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private Ulid $id;

    #[ORM\Column(name: 'email_hash', type: 'string', length: 64, unique: true)]
    private string $emailHash;

    #[ORM\Column(name: 'email_encrypted', type: 'newsletter_encrypted_string')]
    private string $email;

    #[ORM\Column(type: 'string', length: 16, enumType: SubscriberStatus::class)]
    private SubscriberStatus $status;

    #[ORM\Column(name: 'confirm_token', type: 'string', length: 43, nullable: true, unique: true)]
    private ?string $confirmToken;

    #[ORM\Column(name: 'confirm_expires_at', type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmExpiresAt;

    #[ORM\Column(name: 'unsub_token', type: 'string', length: 43, unique: true)]
    private string $unsubToken;

    #[ORM\Column(name: 'enrolled_by', type: 'string', length: 64)]
    private string $enrolledBy;

    #[ORM\Column(name: 'enrolled_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $enrolledAt;

    #[ORM\Column(name: 'confirmed_at', type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(name: 'unsubscribed_at', type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $unsubscribedAt = null;

    #[ORM\Column(name: 'last_sent_at', type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSentAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        Ulid $id,
        EmailAddress $email,
        OpaqueToken $confirmToken,
        \DateTimeImmutable $confirmExpiresAt,
        OpaqueToken $unsubToken,
        string $enrolledBy,
        \DateTimeImmutable $now,
    ) {
        $this->id = $id;
        $this->emailHash = $email->hash();
        $this->email = $email->unmask();
        $this->status = SubscriberStatus::Pending;
        $this->confirmToken = $confirmToken->value();
        $this->confirmExpiresAt = $confirmExpiresAt;
        $this->unsubToken = $unsubToken->value();
        $this->enrolledBy = $enrolledBy;
        $this->enrolledAt = $now;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public static function enrol(
        Ulid $id,
        EmailAddress $email,
        OpaqueToken $confirmToken,
        \DateTimeImmutable $confirmExpiresAt,
        OpaqueToken $unsubToken,
        string $enrolledBy,
        \DateTimeImmutable $now,
    ): self {
        return new self($id, $email, $confirmToken, $confirmExpiresAt, $unsubToken, $enrolledBy, $now);
    }

    public function reenrol(
        OpaqueToken $confirmToken,
        \DateTimeImmutable $confirmExpiresAt,
        OpaqueToken $unsubToken,
        string $enrolledBy,
        \DateTimeImmutable $now,
    ): void {
        if ($this->status !== SubscriberStatus::Unsubscribed) {
            throw new InvalidStatusTransition(sprintf('cannot reenrol from %s', $this->status->value));
        }
        $this->status = SubscriberStatus::Pending;
        $this->confirmToken = $confirmToken->value();
        $this->confirmExpiresAt = $confirmExpiresAt;
        $this->unsubToken = $unsubToken->value();
        $this->enrolledBy = $enrolledBy;
        $this->enrolledAt = $now;
        $this->confirmedAt = null;
        $this->unsubscribedAt = null;
        $this->updatedAt = $now;
    }

    public function confirm(OpaqueToken $token, \DateTimeImmutable $now): void
    {
        if ($this->status === SubscriberStatus::Active) {
            return; // idempotent
        }
        if ($this->status !== SubscriberStatus::Pending) {
            throw new InvalidStatusTransition(sprintf('cannot confirm from %s', $this->status->value));
        }
        if ($this->confirmToken === null || !hash_equals($this->confirmToken, $token->value())) {
            throw new InvalidStatusTransition('confirm token mismatch');
        }
        if ($this->confirmExpiresAt !== null && $now > $this->confirmExpiresAt) {
            throw new InvalidStatusTransition('confirm token expired');
        }
        $this->status = SubscriberStatus::Active;
        $this->confirmToken = null;
        $this->confirmExpiresAt = null;
        $this->confirmedAt = $now;
        $this->updatedAt = $now;
    }

    public function unsubscribe(OpaqueToken $providedToken, OpaqueToken $replacement, \DateTimeImmutable $now): void
    {
        if ($this->status === SubscriberStatus::Unsubscribed) {
            return; // idempotent
        }
        if (!hash_equals($this->unsubToken, $providedToken->value())) {
            throw new InvalidStatusTransition('unsub token mismatch');
        }
        $this->status = SubscriberStatus::Unsubscribed;
        $this->unsubToken = $replacement->value();
        $this->unsubscribedAt = $now;
        $this->updatedAt = $now;
    }

    public function markSent(\DateTimeImmutable $when): void
    {
        $this->lastSentAt = $when;
        $this->updatedAt = $when;
    }

    public function id(): Ulid                                 { return $this->id; }
    public function emailHash(): string                        { return $this->emailHash; }
    public function email(): EmailAddress                      { return EmailAddress::fromString($this->email); }
    public function status(): SubscriberStatus                 { return $this->status; }
    public function confirmToken(): ?OpaqueToken               { return $this->confirmToken === null ? null : OpaqueToken::fromString($this->confirmToken); }
    public function unsubToken(): OpaqueToken                  { return OpaqueToken::fromString($this->unsubToken); }
    public function enrolledBy(): string                       { return $this->enrolledBy; }
    public function enrolledAt(): \DateTimeImmutable           { return $this->enrolledAt; }
    public function confirmedAt(): ?\DateTimeImmutable         { return $this->confirmedAt; }
    public function unsubscribedAt(): ?\DateTimeImmutable      { return $this->unsubscribedAt; }
    public function lastSentAt(): ?\DateTimeImmutable          { return $this->lastSentAt; }
}
