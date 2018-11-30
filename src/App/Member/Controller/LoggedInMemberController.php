<?php

namespace App\Member\Controller;

use App\Member\MemberInterface;
use App\Member\Repository\AuthenticationTokenRepository;
use App\Security\Cors\CorsHeadersAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LoggedInMemberController
{
    use CorsHeadersAwareTrait;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var AuthenticationTokenRepository
     */
    public $authenticationTokenRepository;

    /**
     * @var string
     */
    public $environment;

    /**
     * @var string
     */
    public $allowedOrigin;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getProfile(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $tokenId = $request->request->get(
            'tokenId',
            $request->headers->get('x-auth-admin-token'),
            null
        );

        $corsHeaders = $this->getAccessControlOriginHeaders(
            $this->environment,
            $this->allowedOrigin
        );

        if (is_null($tokenId)) {
            return $this->makeInvalidIdTokenResponse($corsHeaders);
        }

        $memberProperties = $this->authenticationTokenRepository->findByTokenIdentifier($tokenId);
        if (!($memberProperties['member'] instanceof MemberInterface)) {
            return $this->makeInvalidIdTokenResponse($corsHeaders);
        }

        return new JsonResponse(
            [
                'username' => $memberProperties['member']->getTwitterUsername(),
                'grantedRoutes' => json_decode($memberProperties['granted_routes'])
            ],
            200,
            $corsHeaders
        );
    }

    /**
     * @param $corsHeaders
     * @return JsonResponse
     */
    private function makeInvalidIdTokenResponse($corsHeaders): JsonResponse
    {
        return new JsonResponse(
            'Invalid id token',
            403,
            $corsHeaders
        );
    }
}
