<?php

namespace App\Amqp;

use PhpAmqpLib\Message\AMQPMessage;

trait AmqpMessageAwareTrait
{
    /**
     * @param AmqpMessage $message
     * @return mixed
     * @throws \Exception
     */
    public function parseMessage(AMQPMessage $message)
    {
        $options = json_decode(unserialize($message->body), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Valid credentials are required');
        }

        return $options;
    }
}
