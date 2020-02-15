<?php
declare(strict_types=1);

namespace App\Amqp\Message;

/**
 * @package App\Amqp\Message
 */
class FetchMemberLikes extends FetchMemberStatuses
{
    /**
     * @param FetchMemberStatuses $message
     *
     * @return FetchMemberLikes
     */
    public static function from(FetchMemberStatuses $message): self
    {
        return new self(
            $message->screenName(),
            $message->aggregateId(),
            $message->before()
        );
    }
}