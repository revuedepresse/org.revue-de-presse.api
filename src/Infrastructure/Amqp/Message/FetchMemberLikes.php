<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\Message;

class FetchMemberLikes implements FetchPublicationInterface
{
    use FetchPublicationTrait;

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
            $message->token(),
            $message->shouldFetchLikes(),
            $message->dateBeforeWhichStatusAreCollected()
        );
    }
}