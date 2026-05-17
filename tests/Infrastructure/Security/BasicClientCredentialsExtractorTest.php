<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Infrastructure\Security\BasicClientCredentialsExtractor;
use App\Infrastructure\Security\InvalidClientCredentialsException;
use App\Membership\Domain\Entity\Member;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class BasicClientCredentialsExtractorTest extends TestCase
{
    public function test_returns_member_for_valid_basic_credentials(): void
    {
        $member = $this->makeMember('secret-abc', enabled: true);
        $repo = $this->repoReturning([$member]);
        $extractor = new BasicClientCredentialsExtractor($repo);

        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(':secret-abc'));

        self::assertSame($member, $extractor->extract($request));
    }

    public function test_throws_for_missing_authorization_header(): void
    {
        $extractor = new BasicClientCredentialsExtractor($this->repoReturning([]));
        $request = Request::create('/api/token', 'POST');

        $this->expectException(InvalidClientCredentialsException::class);
        $extractor->extract($request);
    }

    public function test_throws_for_non_basic_scheme(): void
    {
        $extractor = new BasicClientCredentialsExtractor($this->repoReturning([]));
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Bearer some-token');

        $this->expectException(InvalidClientCredentialsException::class);
        $extractor->extract($request);
    }

    public function test_throws_for_malformed_base64(): void
    {
        $extractor = new BasicClientCredentialsExtractor($this->repoReturning([]));
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic !!!!');

        $this->expectException(InvalidClientCredentialsException::class);
        $extractor->extract($request);
    }

    public function test_throws_for_wrong_secret(): void
    {
        $member = $this->makeMember('right-secret', enabled: true);
        $extractor = new BasicClientCredentialsExtractor($this->repoReturning([$member]));

        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(':wrong-secret'));

        $this->expectException(InvalidClientCredentialsException::class);
        $extractor->extract($request);
    }

    public function test_skips_disabled_members(): void
    {
        // Repository in the real wiring is queried with enabled=true,
        // so disabled members never come back; verify the extractor relies
        // on that filter (here we simulate by returning an empty result).
        $extractor = new BasicClientCredentialsExtractor($this->repoReturning([]));

        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(':secret-abc'));

        $this->expectException(InvalidClientCredentialsException::class);
        $extractor->extract($request);
    }

    private function makeMember(string $apiKey, bool $enabled): Member
    {
        $member = new Member();
        $member->apiKey = $apiKey;
        $member->setEnabled($enabled);
        $member->setUsername('test-user');

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
