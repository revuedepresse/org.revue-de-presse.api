<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Twitter\Api;

use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\FollowersListAccessorBuilder;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Twitter\Infrastructure\Twitter\Api\Selector\FollowersListSelector;
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
            new FollowersListSelector('thierrymarianne')
        );

        self::assertCount(200, $friendsList->getList());
    }

    public function setUp()
    {
        $this->accessor = FollowersListAccessorBuilder::build();
    }
}