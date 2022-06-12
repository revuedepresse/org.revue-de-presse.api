<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Subscription\Console;

use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\OwnershipAccessorBuilder;
use App\Twitter\Domain\Curation\Repository\OwnershipBatchCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\Api\Selector\MemberOwnershipsBatchSelector;
use App\Twitter\Infrastructure\Subscription\Console\ListSubscriptionsToPublishersListsCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group publishers_list
 */
class ListSubscriptionsToPublishersListsCommandTest extends KernelTestCase
{
    private ListSubscriptionsToPublishersListsCommand $command;

    private CommandTester $commandTester;

    private OwnershipBatchCollectedEventRepositoryInterface $repository;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        /** @var ListSubscriptionsToPublishersListsCommand $command */
        $command = self::$container->get('test.'.ListSubscriptionsToPublishersListsCommand::class);

        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');

        $this->repository = self::$container->get('test.'.OwnershipBatchCollectedEventRepositoryInterface::class);

        $application = new Application($kernel);

        $this->command = $application->find(ListSubscriptionsToPublishersListsCommand::COMMAND_NAME);

        $this->commandTester = new CommandTester($command);

        $this->removeFixtures();
    }

    /**
     * @test
     */
    public function it_can_not_list_publishers_list_subscriptions_when_none_has_been_collected(): void
    {
        $this->commandTester->execute([
            'screen_name' => 'thierrymarianne'
        ]);

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::FAILURE,
            'The return code of this command execution should be unsuccessful.',
        );
    }

    /**
     * @test
     */
    public function it_lists_publishers_list_subscriptions(): void
    {
        // Arrange

        $screenName = 'thierrymarianne';
        $this->repository->collectedOwnershipBatch(
            OwnershipAccessorBuilder::willReturnSomeOwnership(),
            new MemberOwnershipsBatchSelector($screenName)
        );

        // Act

        $this->commandTester->execute([
            'screen_name' => $screenName
        ]);

        // Assert

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The status code of this command should be successful',
        );
    }

    protected function tearDown(): void
    {
        $this->removeFixtures();

        parent::tearDown();
    }

    private function removeFixtures(): void
    {
        $this->entityManager->getConnection()->executeQuery('
            DELETE FROM ownership_batch_collected_event;
        ');
    }
}