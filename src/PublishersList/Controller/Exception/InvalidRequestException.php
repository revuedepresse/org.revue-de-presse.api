<?php

namespace App\PublishersList\Controller\Exception;

use Symfony\Component\HttpFoundation\JsonResponse;

class InvalidRequestException extends \Exception
{
    /**
     * @var \Exception
     */
    public $jsonResponse;

    public static function guardAgainstInvalidRequest(JsonResponse $jsonResponse, $message)
    {
        $exception = new self($message);
        $exception->jsonResponse = $jsonResponse;

        return $exception;
    }
}
