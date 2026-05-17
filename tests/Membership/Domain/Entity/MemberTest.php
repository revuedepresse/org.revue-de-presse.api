<?php
declare(strict_types=1);

namespace App\Tests\Membership\Domain\Entity;

use App\Membership\Domain\Entity\Member;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class MemberTest extends TestCase
{
    public function test_implements_user_interface(): void
    {
        $member = new Member();
        self::assertInstanceOf(UserInterface::class, $member);
    }

    public function test_user_identifier_is_username(): void
    {
        $member = new Member();
        $member->setUsername('alice');
        self::assertSame('alice', $member->getUserIdentifier());
    }

    public function test_default_roles_contain_role_user(): void
    {
        $member = new Member();
        self::assertSame(['ROLE_USER'], $member->getRoles());
    }

    public function test_api_key_round_trip(): void
    {
        $member = new Member();
        $member->apiKey = 'secret-123';
        self::assertSame('secret-123', $member->getApiKey());
    }

    public function test_enabled_round_trip(): void
    {
        $member = new Member();
        $member->setEnabled(true);
        self::assertTrue($member->isEnabled());
    }

    public function test_to_string_returns_user_identifier(): void
    {
        $member = new Member();
        $member->setUsername('bob');
        self::assertSame('bob', (string) $member);
    }

    public function test_erase_credentials_is_idempotent(): void
    {
        $member = new Member();
        $member->eraseCredentials();
        $member->eraseCredentials();
        self::assertTrue(true);
    }
}
