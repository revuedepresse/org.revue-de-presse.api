<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Security\Authentication;

use App\Twitter\Infrastructure\Security\Exception\UnauthorizedRequestException;
use App\Membership\Domain\Entity\MemberInterface;
use App\Membership\Infrastructure\Repository\AuthenticationTokenRepository;
use Symfony\Component\HttpFoundation\Request;
use function array_key_exists;

class HttpAuthenticator
{
    public AuthenticationTokenRepository $authenticationTokenRepository;

    public function authenticateMember(Request $request): ?MemberInterface
    {
        if (!$request->headers->has('x-auth-admin-token')) {
            throw new UnauthorizedRequestException(
                'missing x-auth-admin token'
            );
        }

        $tokenId = $request->headers->get('x-auth-admin-token');

        $memberProperties = $this->authenticationTokenRepository
            ->findByTokenIdentifier($tokenId);

        if (!array_key_exists('member', $memberProperties) ||
            !($memberProperties['member'] instanceof MemberInterface)) {
            throw new UnauthorizedRequestException(
                'Unknown member'
            );
        }

        return $memberProperties['member'];
    }
}
