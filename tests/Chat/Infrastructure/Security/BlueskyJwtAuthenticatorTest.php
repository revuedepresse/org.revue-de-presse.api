<?php
declare(strict_types=1);

namespace App\Tests\Chat\Infrastructure\Security;

use App\Chat\Infrastructure\Security\BlueskyChatUser;
use App\Chat\Infrastructure\Security\BlueskyJwtAuthenticator;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class BlueskyJwtAuthenticatorTest extends TestCase
{
    private const SECRET = 'sufficiently-long-test-secret-that-is-not-empty-AAA';

    public function testEmptySecretThrowsAtConstruction(): void
    {
        $this->expectException(\LogicException::class);
        new BlueskyJwtAuthenticator('');
    }

    public function testSupportsRequiresBearerAuthorizationHeader(): void
    {
        $auth = new BlueskyJwtAuthenticator(self::SECRET);
        $bearer = Request::create('/api/chat/turns');
        $bearer->headers->set('Authorization', 'Bearer abc');
        $basic = Request::create('/api/chat/turns');
        $basic->headers->set('Authorization', 'Basic dXNlcjpwYXNz');
        $none = Request::create('/api/chat/turns');

        self::assertTrue($auth->supports($bearer));
        self::assertFalse($auth->supports($basic));
        self::assertFalse($auth->supports($none));
    }

    public function testValidTokenYieldsBlueskyChatUserPassport(): void
    {
        $jwt = $this->mintValid([
            'sub' => 'did:plc:example',
            'handle' => 'alice.bsky.social',
        ]);
        $passport = $this->authenticate($jwt);

        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        $badge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $badge);
        self::assertSame('did:plc:example', $badge->getUserIdentifier());
        $user = $badge->getUser();
        self::assertInstanceOf(BlueskyChatUser::class, $user);
        self::assertSame('did:plc:example', $user->did);
        self::assertSame('alice.bsky.social', $user->handle);
        self::assertSame(['ROLE_BSKY_USER'], $user->getRoles());
    }

    public function testExpiredTokenIsRejected(): void
    {
        $jwt = $this->mintExpired();
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->authenticate($jwt);
    }

    public function testBadSignatureIsRejected(): void
    {
        $jwtValid = $this->mintValid(['sub' => 'did:plc:example']);
        // Flip a character in the signature segment to corrupt it.
        [$header, $payload, $sig] = explode('.', $jwtValid);
        $tampered = $header . '.' . $payload . '.' . strtr(strrev($sig), ['_' => '-', '-' => '_']);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->authenticate($tampered);
    }

    public function testWrongIssuerIsRejected(): void
    {
        $jwt = $this->mintValid(['sub' => 'did:plc:example'], issuer: 'attacker.example.com');
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->authenticate($jwt);
    }

    public function testMissingSubjectIsRejected(): void
    {
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::SECRET));
        // Build a token deliberately without `sub`.
        $token = $config->builder()
            ->issuedBy('nuxt.revue-de-presse.org')
            ->issuedAt(new \DateTimeImmutable('-5 seconds'))
            ->expiresAt(new \DateTimeImmutable('+60 seconds'))
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->authenticate($token);
    }

    public function testMalformedTokenIsRejected(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->authenticate('not.a.jwt');
    }

    /**
     * @param array{sub?: string, handle?: string} $claims
     */
    private function mintValid(array $claims, string $issuer = 'nuxt.revue-de-presse.org'): string
    {
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::SECRET));
        $builder = $config->builder()
            ->issuedBy($issuer)
            ->issuedAt(new \DateTimeImmutable('-5 seconds'))
            ->expiresAt(new \DateTimeImmutable('+60 seconds'));
        if (isset($claims['sub'])) {
            $builder = $builder->relatedTo($claims['sub']);
        }
        if (isset($claims['handle'])) {
            $builder = $builder->withClaim('handle', $claims['handle']);
        }

        return $builder->getToken($config->signer(), $config->signingKey())->toString();
    }

    private function mintExpired(): string
    {
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::SECRET));

        return $config->builder()
            ->issuedBy('nuxt.revue-de-presse.org')
            ->relatedTo('did:plc:example')
            ->issuedAt(new \DateTimeImmutable('-5 minutes'))
            ->expiresAt(new \DateTimeImmutable('-1 minute'))
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }

    private function authenticate(string $jwt): Passport
    {
        $auth = new BlueskyJwtAuthenticator(self::SECRET);
        $request = Request::create('/api/chat/turns');
        $request->headers->set('Authorization', 'Bearer ' . $jwt);

        return $auth->authenticate($request);
    }
}
