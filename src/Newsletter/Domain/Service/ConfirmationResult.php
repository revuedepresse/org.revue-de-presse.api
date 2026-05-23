<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

enum ConfirmationResult: string
{
    case Confirmed = 'confirmed';
    case AlreadyActive = 'already_active';
    case InvalidOrExpired = 'invalid_or_expired';
}
