<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Collection\Repository;

use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\FollowersListAccessorBuilder;
use App\Twitter\Infrastructure\Curation\Repository\FollowersListCollectedEventRepository;
use App\Twitter\Infrastructure\Api\Selector\FollowersListSelector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group member_subscribee
 */
class FollowersListCollectedEventRepositoryTest extends KernelTestCase
{
    private const SCREEN_NAME = 'thierrymarianne';

    private FollowersListCollectedEventRepository $repository;

    public function setUp(): void
    {
        self::$kernel = self::bootKernel();
        $this->repository = static::getContainer()->get('test.'.FollowersListCollectedEventRepository::class);

        $this->truncateEventStore();
    }

    /**
     * @test
     */
    public function it_should_collect_friends_list_of_a_member(): void
    {
        $accessor = FollowersListAccessorBuilder::build();

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