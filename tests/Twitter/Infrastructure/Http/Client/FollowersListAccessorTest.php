<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Client\Client;

use App\Tests\Twitter\Infrastructure\Http\Builder\Client\FollowersBatchAwareHttpClientBuilder;
use App\Twitter\Domain\Http\Client\CursorAwareHttpClientInterface;
use App\Twitter\Infrastructure\Http\Selector\FollowersListSelector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group member_subscribee
 */
class FollowersBatchAwareHttpClientTest extends KernelTestCase
{
    private CursorAwareHttpClientInterface $accessor;

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
        $this->accessor = FollowersBatchAwareHttpClientBuilder::build();
    }
}
