<?php

namespace App\RequestValidation;

use App\Aggregate\Controller\Exception\InvalidRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait RequestParametersValidationTrait
{
    /**
     * @param Request $request
     * @param         $corsHeaders
     * @return mixed
     */
    private function guardAgainstInvalidParametersEncoding(Request $request, $corsHeaders): array
    {
        $decodedContent = json_decode($request->getContent(), $asArray = true);
        $lastError = json_last_error();
        if ($lastError !== JSON_ERROR_NONE) {
            $exceptionMessage = 'Invalid parameters encoding';
            $jsonResponse = new JsonResponse(
                $exceptionMessage,
                422,
                $corsHeaders
            );

            InvalidRequestException::guardAgainstInvalidRequest($jsonResponse, $exceptionMessage);
        }

        return $decodedContent;
    }

    /**
     * @param $decodedContent
     * @param $corsHeaders
     * @return mixed
     */
    private function guardAgainstInvalidParameters($decodedContent, $corsHeaders): array
    {
        if (!array_key_exists('params', $decodedContent) ||
            !is_array($decodedContent['params'])) {
            $exceptionMessage = 'Invalid params';
            $jsonResponse = new JsonResponse(
                $exceptionMessage,
                422,
                $corsHeaders
            );

            InvalidRequestException::guardAgainstInvalidRequest($jsonResponse, $exceptionMessage);
        }

        return $decodedContent;
    }

}
