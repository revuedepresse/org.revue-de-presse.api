<?php

namespace App\Aggregate\Controller\Exception;

use Symfony\Component\HttpFoundation\JsonResponse;

class InvalidRequestException extends \Exception
{
    /**
     * @var \Exception
     */
    public $jsonResponse;

    /**
     * @param JsonResponse $jsonResponse
     * @param              $message
     * @throws InvalidRequestException
     */
    public static function guardAgainstInvalidRequest(JsonResponse $jsonResponse, $message)
    {
        $exception = new self($message);
        $exception->jsonResponse = $jsonResponse;

        throw $exception;
    }
}
