<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

use App\Newsletter\Domain\ValueObject\OpaqueToken;

enum EnrolmentOutcomeKind: string
{
    case Created = 'created';
    case ResentConfirmation = 'resent_confirmation';
    case AlreadyActive = 'already_active';
    case Reenrolled = 'reenrolled';
}

final class EnrolmentOutcome
{
    public function __construct(
        public readonly EnrolmentOutcomeKind $result,
        public readonly ?OpaqueToken $confirmToken,
    ) {}

    public static function created(OpaqueToken $confirmToken): self
    { return new self(EnrolmentOutcomeKind::Created, $confirmToken); }

    public static function resent(OpaqueToken $confirmToken): self
    { return new self(EnrolmentOutcomeKind::ResentConfirmation, $confirmToken); }

    public static function alreadyActive(): self
    { return new self(EnrolmentOutcomeKind::AlreadyActive, null); }

    public static function reenrolled(OpaqueToken $confirmToken): self
    { return new self(EnrolmentOutcomeKind::Reenrolled, $confirmToken); }
}
