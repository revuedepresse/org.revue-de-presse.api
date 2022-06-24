<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Curation\Repository;

use App\Tests\Twitter\Infrastructure\Http\Builder\Client\FriendsBatchAwareHttpClientBuilder;
use App\Twitter\Infrastructure\Curation\Repository\FriendsListCollectedEventRepository;
use App\Twitter\Infrastructure\Http\Selector\FriendsListSelector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group member_subscription
 */
class FriendsListCollectedEventRepositoryTest extends KernelTestCase
{
    private const SCREEN_NAME = 'mipsytipsy';

    private FriendsListCollectedEventRepository $repository;

    public function setUp(): void
    {
        self::$kernel = self::bootKernel();
        $this->repository = static::getContainer()->get('test.'.FriendsListCollectedEventRepository::class);

        $this->truncateEventStore();
    }

    /**
     * @test
     */
    public function it_should_collect_friends_list_of_a_member(): void
    {
        $accessor = FriendsBatchAwareHttpClientBuilder::build();

        $friendsList = $this->repository->collectedList(
            $accessor,
            new FriendsListSelector(
                self::SCREEN_NAME
            )
        );

        self::assertEquals(200, $friendsList->count());

        $memberFriendsListCollectedEvents = $this->repository->findBy(['screenName' => self::SCREEN_NAME]);
        self::assertCount(1, $memberFriendsListCollectedEvents);
    }

    protected function tearDown(): void
    {
        $this->truncateEventStore();
    }

    private function truncateEventStore(): void
    {
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $connection = $entityManager->getConnection();
        $connection->executeQuery('TRUNCATE TABLE member_friends_list_collected_event');
    }
}