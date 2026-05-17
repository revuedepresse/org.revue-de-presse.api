<?php
declare(strict_types=1);

namespace App\Tests\Security\Infrastructure\Symfony;

use App\Tests\Security\Domain\InMemoryAccessTokenStore;

use App\Security\Infrastructure\Symfony\ApiAccessTokenHandler;
use App\Membership\Domain\Entity\Member;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiAccessTokenHandlerTest extends TestCase
{
    public function test_returns_user_badge_for_active_token(): void
    {
        $store = new InMemoryAccessTokenStore();
        $member = new Member();
        $member->setUsername('alice');
        $repo = $this->repoReturning($member);

        $store->put('plaintext-abc', '42', 900);
        $handler = new ApiAccessTokenHandler($store, $repo);

        $badge = $handler->getUserBadgeFrom('plaintext-abc');

        self::assertInstanceOf(UserBadge::class, $badge);
        self::assertSame('42', $badge->getUserIdentifier());
        $loader = $badge->getUserLoader();
        self::assertSame($member, $loader('42'));
    }

    public function test_throws_for_unknown_token(): void
    {
        $store = new InMemoryAccessTokenStore();
        $repo = $this->repoReturning(null);
        $handler = new ApiAccessTokenHandler($store, $repo);

        $this->expectException(BadCredentialsException::class);
        $handler->getUserBadgeFrom('never-minted');
    }

    public function test_throws_for_expired_token(): void
    {
        $store = new InMemoryAccessTokenStore();
        $store->put('expired-abc', '42', ttlSeconds: -1);
        $repo = $this->repoReturning(new Member());
        $handler = new ApiAccessTokenHandler($store, $repo);

        $this->expectException(BadCredentialsException::class);
        $handler->getUserBadgeFrom('expired-abc');
    }

    private function repoReturning(?Member $member): EntityRepository
    {
        return new class($member) extends EntityRepository {
            public function __construct(private readonly ?Member $member)
            {
            }
            public function find($id, $lockMode = null, $lockVersion = null): ?Member
            {
                return $this->member;
            }
        };
    }
}
