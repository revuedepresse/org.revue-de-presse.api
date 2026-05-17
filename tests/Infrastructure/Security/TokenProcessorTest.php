<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use ApiPlatform\Metadata\Post;
use App\Infrastructure\Security\AccessTokenDto;
use App\Infrastructure\Security\AccessTokenMinter;
use App\Infrastructure\Security\BasicClientCredentialsExtractor;
use App\Infrastructure\Security\InvalidClientCredentialsException;
use App\Infrastructure\Security\TokenProcessor;
use App\Membership\Domain\Entity\Member;
use Doctrine\ORM\EntityRepository;
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
        $extractor = new BasicClientCredentialsExtractor($this->repoReturning([$member]));

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
        $extractor = new BasicClientCredentialsExtractor($this->repoReturning([]));

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

    private function repoReturning(array $members): EntityRepository
    {
        return new class($members) extends EntityRepository {
            public function __construct(private readonly array $members)
            {
            }
            public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
            {
                return $this->members;
            }
        };
    }
}
