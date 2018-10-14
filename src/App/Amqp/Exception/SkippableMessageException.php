<?php

namespace App\Amqp\Exception;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Whisperer;

class SkippableMessageException extends \Exception
{
    public $shouldSkipMessageConsumption;

    public static function stopMessageConsumption()
    {
        $exception = new self();
        $exception->shouldSkipMessageConsumption = true;

        throw $exception;
    }

    /**
     * @param Whisperer|null $whisperer
     * @param \stdClass|null $member
     * @throws SkippableMessageException
     */
    public static function continueMessageConsumption()
    {
        $exception = new self();
        $exception->shouldSkipMessageConsumption = false;

        throw $exception;
    }
}
