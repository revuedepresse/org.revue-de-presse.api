<?php

namespace App\Member\Controller;

use App\Member\Accessor\MemberProfileAccessor;
use App\RequestValidation\RequestParametersValidationTrait;
use App\Security\Cors\CorsHeadersAwareTrait;
use App\Security\Exception\UnauthorizedRequestException;
use App\Security\HttpAuthenticator;
use App\Serialization\JsonEncodingAwareInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MemberController
{
    use CorsHeadersAwareTrait;
    use RequestParametersValidationTrait;

    /**
     * @var string
     */
    public $allowedOrigin;

    /**
     * @var string
     */
    public $environment;

    /**
     * @var HttpAuthenticator
     */
    public $httpAuthenticator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var MemberProfileAccessor
     */
    public $memberProfileAccessor;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshProfile(Request $request)
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

        $member = $this->memberProfileAccessor->refresh($jsonResponseOrUsername);

        if (!$member instanceof JsonEncodingAwareInterface) {
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


        if (!array_key_exists('memberName', $decodedContent['params'])) {
            return $invalidMemberNameResponse;
        }

        $username = filter_var($decodedContent['params']['memberName'], FILTER_SANITIZE_STRING);
        if (strlen($username) === 0) {
            return $invalidMemberNameResponse;
        }

        return $username;
    }
}
