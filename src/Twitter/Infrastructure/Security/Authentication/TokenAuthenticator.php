<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Security\Authentication;

use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class TokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use MemberRepositoryTrait;
    use LoggerTrait;

    public function supports(Request $request): ?bool
    {
        $path = $request->getPathInfo();

        if (str_starts_with($path, '/api/callback') || str_starts_with($path, '/api/healthcheck')) {
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        if ($request->isMethod('OPTIONS')) {
            $member = $this->memberRepository->getMemberHavingApiKey();
            $apiKey = $member->getApiKey();
        } else {
            $apiKey = $request->headers->get('x-auth-token');
        }

        if ($apiKey === null || $apiKey === '') {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        return new SelfValidatingPassport(new UserBadge($apiKey));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->getPathInfo() === '/api/healthcheck') {
            return new Response(
                null,
                Response::HTTP_NO_CONTENT,
                ['status' => 'Everything is ok ^_^']
            );
        }

        return new JsonResponse(
            ['message' => strtr($exception->getMessageKey(), $exception->getMessageData())],
            Response::HTTP_FORBIDDEN
        );
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(
            ['message' => 'Authentication Required'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
