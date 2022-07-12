<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Curation\Repository;

use App\Tests\Twitter\Infrastructure\Http\Builder\Client\FollowersBatchAwareHttpClientBuilder;
use App\Twitter\Domain\Curation\Repository\PaginatedBatchCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\Curation\Repository\FollowersListCollectedEventRepository;
use App\Twitter\Infrastructure\Curation\Repository\PaginatedBatchListCollectedEventRepository;
use App\Twitter\Infrastructure\Http\Selector\FollowersListSelector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group member_subscribee
 */
class FollowersListCollectedEventRepositoryTest extends KernelTestCase
{
    private const SCREEN_NAME = 'thierrymarianne';

    private PaginatedBatchCollectedEventRepositoryInterface $repository;

    public function setUp(): void
    {
        self::$kernel = self::bootKernel();
        $this->repository = static::getContainer()->get('test.'.FollowersListCollectedEventRepository::class);

        static::getContainer()->get('test.doctrine.dbal.connection')
            ->executeQuery( 'CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        $this->truncateEventStore();
    }

    /**
     * @test
     */
    public function it_should_collect_friends_list_of_a_member(): void
    {
        $accessor = FollowersBatchAwareHttpClientBuilder::build();

        $followersList = $this->repository->collectedList(
            $accessor,
            new FollowersListSelector(self::SCREEN_NAME)
        );

        self::assertEquals(200, $followersList->count());

        $memberFollowersListCollectedEvents = $this->repository->findBy(['screenName' => self::SCREEN_NAME]);
        self::assertCount(1, $memberFollowersListCollectedEvents);
    }

    protected function tearDown(): void
    {
        $this->truncateEventStore();
    }

    private function truncateEventStore(): void
    {
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $connection = $entityManager->getConnection();
        $connection->executeQuery('TRUNCATE TABLE member_followers_list_collected_event');
    }
}
