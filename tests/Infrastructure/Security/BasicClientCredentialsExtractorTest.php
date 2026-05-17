<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Infrastructure\Security\BasicClientCredentialsExtractor;
use App\Infrastructure\Security\InvalidClientCredentialsException;
use App\Membership\Domain\Entity\Member;
use App\Twitter\Infrastructure\Repository\Membership\MemberRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class BasicClientCredentialsExtractorTest extends TestCase
{
    public function test_returns_member_for_valid_basic_credentials(): void
    {
        $member = $this->makeMember('secret-abc', enabled: true);
        $repo = $this->repoMatching('secret-abc', $member);
        $extractor = new BasicClientCredentialsExtractor($repo);

        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(':secret-abc'));

        self::assertSame($member, $extractor->extract($request));
    }

    public function test_throws_for_missing_authorization_header(): void
    {
        $extractor = new BasicClientCredentialsExtractor($this->repoMatching('any', null));
        $request = Request::create('/api/token', 'POST');

        $this->expectException(InvalidClientCredentialsException::class);
        $extractor->extract($request);
    }

    public function test_throws_for_non_basic_scheme(): void
    {
        $extractor = new BasicClientCredentialsExtractor($this->repoMatching('any', null));
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Bearer some-token');

        $this->expectException(InvalidClientCredentialsException::class);
        $extractor->extract($request);
    }

    public function test_throws_for_malformed_base64(): void
    {
        $extractor = new BasicClientCredentialsExtractor($this->repoMatching('any', null));
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic !!!!');

        $this->expectException(InvalidClientCredentialsException::class);
        $extractor->extract($request);
    }

    public function test_throws_for_wrong_secret(): void
    {
        $member = $this->makeMember('right-secret', enabled: true);
        // Repository returns null for any submitted secret other than the configured one.
        $extractor = new BasicClientCredentialsExtractor($this->repoMatching('right-secret', $member));

        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(':wrong-secret'));

        $this->expectException(InvalidClientCredentialsException::class);
        $extractor->extract($request);
    }

    public function test_repository_lookup_does_not_load_all_members(): void
    {
        $member = $this->makeMember('secret-abc', enabled: true);
        $repo = $this->repoMatching('secret-abc', $member);
        $extractor = new BasicClientCredentialsExtractor($repo);

        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(':secret-abc'));

        $extractor->extract($request);

        // The extractor exercises findEnabledByApiKey exactly once and never findBy/findAll.
        self::assertSame(1, $repo->findEnabledByApiKeyCalls);
        self::assertSame(0, $repo->findAllCalls);
        self::assertSame(0, $repo->findByCalls);
    }

    private function makeMember(string $apiKey, bool $enabled): Member
    {
        $member = new Member();
        $member->apiKey = $apiKey;
        $member->setEnabled($enabled);
        $member->setUsername('test-user');

        return $member;
    }

    /**
     * Builds a MemberRepository stub that returns $member only when the submitted
     * secret matches $expected; counts how many times each lookup method is called
     * so the test can assert the extractor doesn't fan out to findBy / findAll.
     */
    private function repoMatching(string $expected, ?Member $member): MemberRepository
    {
        return new class($expected, $member) extends MemberRepository {
            public int $findEnabledByApiKeyCalls = 0;
            public int $findAllCalls = 0;
            public int $findByCalls = 0;

            public function __construct(
                private readonly string $expected,
                private readonly ?Member $member,
            ) {
            }

            public function findEnabledByApiKey(string $submittedSecret): ?Member
            {
                $this->findEnabledByApiKeyCalls++;

                return hash_equals($this->expected, $submittedSecret) ? $this->member : null;
            }

            public function findAll(): array
            {
                $this->findAllCalls++;

                return [];
            }

            public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
            {
                $this->findByCalls++;

                return [];
            }
        };
    }
}
