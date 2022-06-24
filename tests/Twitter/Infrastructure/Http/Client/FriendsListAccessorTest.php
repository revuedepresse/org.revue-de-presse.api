<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Client\Client;

use App\Twitter\Domain\Http\Client\CursorAwareHttpClientInterface;
use App\Twitter\Infrastructure\Http\Selector\FriendsListSelector;
use App\Tests\Twitter\Infrastructure\Http\Builder\Client\FriendsBatchAwareHttpClientBuilder;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group member_subscription
 */
class FriendsListAccessorTest extends KernelTestCase
{
    private CursorAwareHttpClientInterface $accessor;

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
        $this->accessor = FriendsBatchAwareHttpClientBuilder::build();
    }
}
