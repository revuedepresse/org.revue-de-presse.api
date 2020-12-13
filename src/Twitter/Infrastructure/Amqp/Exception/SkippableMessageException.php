<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Exception;

/**
 * @package App\Twitter\Infrastructure\Amqp\Exception
 */
class SkippableMessageException extends \Exception
{
    public bool $shouldSkipMessageConsumption;

    /**
     * @throws SkippableMessageException
     */
    public static function stopMessageConsumption(): void
    {
        $exception = new self();
        $exception->shouldSkipMessageConsumption = true;

        throw $exception;
    }

    /**
     * @throws SkippableMessageException
     */
    public static function continueMessageConsumption(): void
    {
        $exception = new self();
        $exception->shouldSkipMessageConsumption = false;

        throw $exception;
    }
}
