<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

enum UnsubscribeResult: string
{
    case Unsubscribed = 'unsubscribed';
    case AlreadyUnsubscribed = 'already_unsubscribed';
    case InvalidToken = 'invalid_token';
}
