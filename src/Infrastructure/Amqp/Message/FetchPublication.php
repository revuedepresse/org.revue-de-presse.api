<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\Message;

interface FetchPublication
{
    public const AGGREGATE_ID = 'aggregate_id';
    public const SCREEN_NAME = 'screen_name';
    public const BEFORE = 'before';
}