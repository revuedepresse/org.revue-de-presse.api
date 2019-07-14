<?php

namespace App\Security;

use App\Member\MemberInterface;
use App\Member\Repository\AuthenticationTokenRepository;
use App\Security\Exception\UnauthorizedRequestException;
use Symfony\Component\HttpFoundation\Request;

class HttpAuthenticator
{
    /**
     * @var AuthenticationTokenRepository
     */
    public $authenticationTokenRepository;

    /**
     * @param Request $request
     * @return MemberInterface|null
     * @throws UnauthorizedRequestException
     */
    public function authenticateMember(Request $request): ?MemberInterface
    {
        if (!$request->headers->has('x-auth-admin-token')) {
            throw new UnauthorizedRequestException();
        }

        $tokenId = $request->headers->get('x-auth-admin-token');
        $memberProperties = $this->authenticationTokenRepository->findByTokenIdentifier($tokenId);

        if (!array_key_exists('member', $memberProperties) ||
            !($memberProperties['member'] instanceof MemberInterface)) {
            throw new UnauthorizedRequestException();
        }

        return $memberProperties['member'];
    }

}
