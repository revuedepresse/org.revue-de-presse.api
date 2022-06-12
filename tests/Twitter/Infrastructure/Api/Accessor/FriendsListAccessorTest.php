<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\Accessor;

use App\Twitter\Domain\Api\Accessor\ListAccessorInterface;
use App\Twitter\Infrastructure\Api\Selector\FriendsListSelector;
use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\FriendsListAccessorBuilder;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group member_subscription
 */
class FriendsListAccessorTest extends KernelTestCase
{
    private ListAccessorInterface $accessor;

    /**
     * @test
     */
    public function it_should_get_friends_list_at_default_cursor(): void
    {
        $friendsList = $this->accessor->getListAtCursor(
            new FriendsListSelector('mipsytipsy')
        );

        self::assertCount(200, $friendsList->getList());
    }

    public function setUp(): void
    {
        $this->accessor = FriendsListAccessorBuilder::build();
    }
}
