<?php
declare (strict_types=1);

namespace App\Tests\Infrastructure\Twitter\Api;

use App\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Infrastructure\Twitter\Api\Selector\FollowersListSelector;
use App\Infrastructure\Twitter\Api\Selector\FriendsListSelector;
use App\Tests\Builder\Twitter\Api\Accessor\FollowersListAccessorBuilder;
use App\Tests\Builder\Twitter\Api\Accessor\FriendsListAccessorBuilder;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group member_subscribee
 */
class FollowersListAccessorTest extends KernelTestCase
{
    private ListAccessorInterface $accessor;

    /**
     * @test
     */
    public function it_should_get_followers_list_at_default_cursor(): void
    {
        $friendsList = $this->accessor->getListAtCursor(
            new FollowersListSelector(
                UuidV4::uuid4(),
                'thierrymarianne',
            )
        );

        self::assertCount(200, $friendsList->getList());
    }

    public function setUp()
    {
        $this->accessor = FollowersListAccessorBuilder::make();
    }
}