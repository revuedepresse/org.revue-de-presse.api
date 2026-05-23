<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\ValueObject;

enum SubscriberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Unsubscribed = 'unsubscribed';
}
