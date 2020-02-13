<?php
declare(strict_types=1);

namespace App\Amqp\Exception;

/**
 * @package App\Amqp\Exception
 */
class SkippableMessageException extends \Exception
{
    public bool $shouldSkipMessageConsumption;

    /**
     * @throws SkippableMessageException
     */
    public static function stopMessageConsumption()
    {
        $exception = new self();
        $exception->shouldSkipMessageConsumption = true;

        throw $exception;
    }

    /**
     * @throws SkippableMessageException
     */
    public static function continueMessageConsumption()
    {
        $exception = new self();
        $exception->shouldSkipMessageConsumption = false;

        throw $exception;
    }
}
