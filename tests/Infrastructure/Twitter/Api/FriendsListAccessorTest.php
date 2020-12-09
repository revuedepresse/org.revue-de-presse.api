<?php
declare (strict_types=1);

namespace App\Tests\Infrastructure\Twitter\Api;

use App\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Tests\Builder\Twitter\Api\Accessor\FriendsListAccessorBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group member_subscriptions
 */
class FriendsListAccessorTest extends KernelTestCase
{
    private ListAccessorInterface $accessor;

    /**
     * @test
     */
    public function it_should_get_friends_list_at_default_cursor(): void
    {
        $friendsList = $this->accessor->getListAtDefaultCursor('mipsytipsy');

        self::assertCount(200, $friendsList->getList());
    }

    public function setUp()
    {
        $this->accessor = FriendsListAccessorBuilder::make();
    }
}