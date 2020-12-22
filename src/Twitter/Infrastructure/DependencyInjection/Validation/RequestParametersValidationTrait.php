<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Validation;

use App\PublishersList\Controller\Exception\InvalidRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use const JSON_ERROR_NONE;
use const JSON_THROW_ON_ERROR;

trait RequestParametersValidationTrait
{
    /**
     * @param Request $request
     * @param         $corsHeaders
     * @return mixed
     */
    private function guardAgainstInvalidParametersEncoding(Request $request, $corsHeaders): array
    {
        $decodedContent = json_decode(
            $request->getContent(),
            $asArray = true,
            512,
            JSON_THROW_ON_ERROR
        );
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
        if (!\array_key_exists('params', $decodedContent) ||
            !\is_array($decodedContent['params'])) {
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
