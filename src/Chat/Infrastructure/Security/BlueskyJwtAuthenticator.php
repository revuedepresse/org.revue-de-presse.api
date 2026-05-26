<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Security;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
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
    private readonly Configuration $jwtConfig;
    private readonly LoggerInterface $logger;

    public function __construct(
        #[\SensitiveParameter] string $sharedSecret,
        private readonly string $expectedIssuer = 'nuxt.revue-de-presse.org',
        ?LoggerInterface $logger = null,
    ) {
        if ($sharedSecret === '') {
            throw new \LogicException('API_JWT_SECRET must not be empty');
        }
        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($sharedSecret),
        );
        $this->logger = $logger ?? new NullLogger();
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with((string) $request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $header = (string) $request->headers->get('Authorization');
        $jwt = substr($header, 7);

        try {
            $token = $this->jwtConfig->parser()->parse($jwt);
        } catch (\Throwable $e) {
            $this->logger->info('chat.auth.invalid_token', ['error' => $e::class]);
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }
        if (!$token instanceof Token\Plain) {
            throw new CustomUserMessageAuthenticationException('Invalid token shape');
        }

        try {
            $this->jwtConfig->validator()->assert(
                $token,
                new SignedWith($this->jwtConfig->signer(), $this->jwtConfig->signingKey()),
                new IssuedBy($this->expectedIssuer),
                new StrictValidAt(SystemClock::fromUTC(), new \DateInterval('PT0S')),
            );
        } catch (\Throwable $e) {
            $this->logger->info('chat.auth.invalid_token', ['error' => $e::class, 'message' => $e->getMessage()]);
            throw new CustomUserMessageAuthenticationException($e->getMessage());
        }

        $did = $token->claims()->get('sub');
        if (!is_string($did) || $did === '') {
            throw new CustomUserMessageAuthenticationException('Missing subject');
        }

        $handle = $token->claims()->get('handle');
        $handleStr = is_string($handle) ? $handle : null;

        return new SelfValidatingPassport(
            new UserBadge($did, fn (): BlueskyChatUser => new BlueskyChatUser($did, $handleStr)),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(
            ['error' => 'unauthorized', 'message' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
