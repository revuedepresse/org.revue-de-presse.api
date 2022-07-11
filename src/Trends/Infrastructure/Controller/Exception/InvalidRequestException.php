<?php

namespace App\Trends\Infrastructure\Controller\Exception;

use Symfony\Component\HttpFoundation\JsonResponse;

class InvalidRequestException extends \Exception
{
    public JsonResponse $jsonResponse;

    public static function guardAgainstInvalidRequest(JsonResponse $jsonResponse, $message)
    {
        $exception = new self($message);
        $exception->jsonResponse = $jsonResponse;

        return $exception;
    }
}
