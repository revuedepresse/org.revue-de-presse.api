<?php
declare (strict_types=1);

namespace App\Tests\Infrastructure\Twitter\Api;

use App\Infrastructure\Twitter\Api\Accessor\FriendsAccessor;
use App\Tests\Test\Builder\Twitter\Api\Accessor\FriendsAccessorBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group member_subscriptions
 */
class FriendsAccessorTest extends KernelTestCase
{
    private FriendsAccessor $accessor;

    /**
     * @test
     */
    public function it_should_get_friends_list_at_default_cursor(): void
    {
        $friendsList = $this->accessor->getMemberFriendsListAtDefaultCursor('mipsytipsy');

        self::assertCount(200, $friendsList->getFriendsList());
    }

    /**
     * @test
     */
    public function it_should_get_friends_list(): void
    {
        $friendsList = $this->accessor->getMemberFriendsList('mipsytipsy');

        self::assertGreaterThan(200, $friendsList->getFriendsList());
    }

    public function setUp()
    {
        $this->accessor = FriendsAccessorBuilder::make();
    }
}