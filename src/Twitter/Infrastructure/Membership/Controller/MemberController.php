<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Membership\Controller;

use App\Twitter\Domain\Membership\Exception\InvalidMemberException;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Security\Authentication\HttpAuthenticator;
use App\Twitter\Infrastructure\Security\Cors\CorsHeadersAwareTrait;
use App\Twitter\Infrastructure\Security\Exception\UnauthorizedRequestException;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\MemberProfileAccessor;
use App\Twitter\Infrastructure\DependencyInjection\Validation\RequestParametersValidationTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use const FILTER_SANITIZE_STRING;

class MemberController
{
    use CorsHeadersAwareTrait;
    use RequestParametersValidationTrait;
    use LoggerTrait;

    public MemberProfileAccessor $memberProfileAccessor;

    public function refreshProfile(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $corsHeaders = $this->getAccessControlOriginHeaders(
            $this->environment,
            $this->allowedOrigin
        );
        $unauthorizedJsonResponse = new JsonResponse(
            ['error' => 'Unauthorized request'],
            403,
            $corsHeaders
        );

        try {
            $this->httpAuthenticator->authenticateMember($request);
        } catch (UnauthorizedRequestException $exception) {
            return $unauthorizedJsonResponse;
        }

        $decodedContent = $this->guardAgainstInvalidParametersEncoding($request, $corsHeaders);
        $decodedContent = $this->guardAgainstInvalidParameters($decodedContent, $corsHeaders);
        $jsonResponseOrUsername = $this->guardAgainstInvalidUsername($corsHeaders, $decodedContent);

        if ($jsonResponseOrUsername instanceof JsonResponse) {
            return $jsonResponseOrUsername;
        }

        try {
            /** @var string $jsonResponseOrUsername */
            $member = $this->memberProfileAccessor->refresh($jsonResponseOrUsername);
        } catch (InvalidMemberException $exception) {
            return new JsonResponse('Invalid member', 422, $corsHeaders);
        }

        return new JsonResponse(
            $member->encodeAsJson(),
            200,
            $corsHeaders,
            true
        );
    }

    /**
     * @param array $corsHeaders
     * @param array $decodedContent
     * @return mixed|JsonResponse
     */
    private function guardAgainstInvalidUsername(array $corsHeaders, array $decodedContent)
    {
        $invalidMemberNameResponse = new JsonResponse(
            ['error' => 'Invalid member name'],
            422,
            $corsHeaders
        );


        if (!\array_key_exists('memberName', $decodedContent['params'])) {
            return $invalidMemberNameResponse;
        }

        $username = filter_var($decodedContent['params']['memberName'], FILTER_SANITIZE_STRING);
        if ($username === '') {
            return $invalidMemberNameResponse;
        }

        return $username;
    }
}
