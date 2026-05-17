<?php
declare(strict_types=1);

namespace App\Tests\Security\Infrastructure\ApiPlatform\State;

use App\Tests\Security\Domain\InMemoryAccessTokenStore;

use ApiPlatform\Metadata\Post;
use App\Security\Domain\AccessTokenDto;
use App\Security\Domain\AccessTokenMinter;
use App\Security\Infrastructure\Symfony\BasicClientCredentialsExtractor;
use App\Security\Domain\InvalidClientCredentialsException;
use App\Security\Infrastructure\ApiPlatform\State\TokenProcessor;
use App\Membership\Domain\Entity\Member;
use App\Membership\Infrastructure\Repository\MemberRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TokenProcessorTest extends TestCase
{
    public function test_returns_access_token_dto_for_valid_credentials(): void
    {
        $member = $this->makeMember('secret-abc');
        $store = new InMemoryAccessTokenStore();
        $minter = new AccessTokenMinter($store, ttlSeconds: 900);
        $extractor = new BasicClientCredentialsExtractor($this->repoReturning('secret-abc', $member));

        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(':secret-abc'));
        $stack = new RequestStack();
        $stack->push($request);

        $processor = new TokenProcessor($minter, $extractor, $stack);
        $dto = $processor->process(null, new Post());

        self::assertInstanceOf(AccessTokenDto::class, $dto);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $dto->access_token);
        self::assertSame('Bearer', $dto->token_type);
        self::assertSame(900, $dto->expires_in);
    }

    public function test_throws_for_invalid_credentials(): void
    {
        $store = new InMemoryAccessTokenStore();
        $minter = new AccessTokenMinter($store, ttlSeconds: 900);
        $extractor = new BasicClientCredentialsExtractor($this->repoReturning('expected', null));

        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(':wrong'));
        $stack = new RequestStack();
        $stack->push($request);

        $processor = new TokenProcessor($minter, $extractor, $stack);

        $this->expectException(InvalidClientCredentialsException::class);
        $processor->process(null, new Post());
    }

    private function makeMember(string $apiKey): Member
    {
        $member = new Member();
        $member->apiKey = $apiKey;
        $member->setEnabled(true);
        $member->setUsername('user');

        return $member;
    }

    private function repoReturning(string $expected, ?Member $member): MemberRepository
    {
        return new class($expected, $member) extends MemberRepository {
            public function __construct(
                private readonly string $expected,
                private readonly ?Member $member,
            ) {
            }

            public function findEnabledByApiKey(string $submittedSecret): ?Member
            {
                return hash_equals($this->expected, $submittedSecret) ? $this->member : null;
            }
        };
    }
}
