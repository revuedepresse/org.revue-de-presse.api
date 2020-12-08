<?php
declare (strict_types=1);

namespace App\Tests\Infrastructure\Collection\Repository;

use App\Infrastructure\Collection\Repository\MemberFriendsListCollectedEventRepository;
use App\Tests\Builder\Twitter\Api\Accessor\FriendsAccessorBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group member_subscription
 */
class MemberFriendsListCollectedEventRepositoryTest extends KernelTestCase
{
    private const SCREEN_NAME = 'mipsytipsy';

    private MemberFriendsListCollectedEventRepository $repository;

    public function setUp(): void
    {
        self::$kernel = self::bootKernel();
        self::$container = self::$kernel->getContainer();
        $this->repository = self::$container->get('test.'.MemberFriendsListCollectedEventRepository::class);

        $this->truncateEventStore();
    }

    /**
     * @test
     */
    public function it_should_collect_friends_list_of_a_member(): void
    {
        $accessor = FriendsAccessorBuilder::make();

        $friendsList = $this->repository->collectedMemberFriendsList(
            $accessor,
            [$this->repository::OPTION_SCREEN_NAME => self::SCREEN_NAME]
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
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $connection = $entityManager->getConnection();
        $connection->executeQuery('TRUNCATE TABLE member_friends_list_collected_event');
    }
}