<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\Controller;

use App\Membership\Domain\Entity\MemberInterface;
use App\Membership\Infrastructure\Repository\AuthenticationTokenRepository;
use App\Twitter\Infrastructure\Security\Cors\CorsHeadersAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LoggedInMemberController
{
    use CorsHeadersAwareTrait;

    public LoggerInterface $logger;

    /**
     * @var AuthenticationTokenRepository
     */
    public AuthenticationTokenRepository $authenticationTokenRepository;

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
        );

        $corsHeaders = $this->getAccessControlOriginHeaders(
            $this->environment,
            $this->allowedOrigin
        );

        if (is_null($tokenId)) {
            return $this->makeInvalidIdTokenResponse($corsHeaders);
        }

        $memberProperties = $this->authenticationTokenRepository->findByTokenIdentifier($tokenId);
        if (!array_key_exists('member', $memberProperties) ||
            !($memberProperties['member'] instanceof MemberInterface)) {
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
