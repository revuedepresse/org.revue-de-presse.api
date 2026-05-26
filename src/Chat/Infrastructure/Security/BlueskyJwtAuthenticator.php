<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Security;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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

/**
 * Verifies the HS256 JWT minted by Nuxt (sub = DID, exp ≤ now + 60 s).
 * No DB lookup — the chat firewall is stateless.
 */
final class BlueskyJwtAuthenticator extends AbstractAuthenticator
{
    private readonly LoggerInterface $logger;

    public function __construct(
        #[\SensitiveParameter] private readonly string $sharedSecret,
        private readonly string $expectedIssuer = 'nuxt.revue-de-presse.org',
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with((string) $request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $header = (string) $request->headers->get('Authorization');
        $jwt = substr($header, 7);

        try {
            $payload = JWT::decode($jwt, new Key($this->sharedSecret, 'HS256'));
        } catch (ExpiredException) {
            throw new CustomUserMessageAuthenticationException('Token expired');
        } catch (SignatureInvalidException) {
            throw new CustomUserMessageAuthenticationException('Invalid signature');
        } catch (\Throwable $e) {
            $this->logger->info('chat.auth.invalid_token', ['error' => $e::class]);
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }

        $iss = $payload->iss ?? null;
        if ($iss !== $this->expectedIssuer) {
            throw new CustomUserMessageAuthenticationException('Unexpected issuer');
        }

        $did = $payload->sub ?? null;
        if (!is_string($did) || $did === '') {
            throw new CustomUserMessageAuthenticationException('Missing subject');
        }

        $handle = isset($payload->handle) && is_string($payload->handle) ? $payload->handle : null;

        return new SelfValidatingPassport(
            new UserBadge($did, fn (): BlueskyChatUser => new BlueskyChatUser($did, $handle)),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => 'unauthorized', 'message' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
