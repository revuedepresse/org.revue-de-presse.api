<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

class FetchAuthoredMemberStatus implements FetchAuthoredTweetInterface
{
    use FetchAuthoredTweetTrait;
}

