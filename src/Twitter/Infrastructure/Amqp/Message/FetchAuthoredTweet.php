<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

class FetchAuthoredTweet implements FetchAuthoredTweetInterface
{
    use FetchAuthoredTweetTrait;
}

