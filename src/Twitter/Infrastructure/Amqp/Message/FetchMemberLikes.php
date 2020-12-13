<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

class FetchMemberLikes implements FetchPublicationInterface
{
    use FetchPublicationTrait;

    /**
     * @param FetchPublicationInterface $message
     *
     * @return FetchMemberLikes
     */
    public static function from(FetchPublicationInterface $message): self
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