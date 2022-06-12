<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\Accessor;

use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\FollowersListAccessorBuilder;
use App\Twitter\Domain\Api\Accessor\ListAccessorInterface;
use App\Twitter\Infrastructure\Api\Selector\FollowersListSelector;
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

    public function setUp(): void
    {
        $this->accessor = FollowersListAccessorBuilder::build();
    }
}
