<?php

declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\PublishersList\Console;

use App\Tests\Twitter\Infrastructure\Http\Builder\Client\HttpClientBuilder;
use App\Twitter\Infrastructure\PublishersList\Console\ImportMemberPublishersListsCommand;
use App\Twitter\Infrastructure\Subscription\Console\ListSubscriptionsToPublishersListsCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group publishers_list
 */
class ImportMemberPublishersListsCommandTest extends KernelTestCase
{
    public const SCREEN_NAME = 'dev_obs';

    private ImportMemberPublishersListsCommand $command;

    private CommandTester $commandTester;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        /** @var ListSubscriptionsToPublishersListsCommand $command */
        $command = static::getContainer()->get('test.'.ImportMemberPublishersListsCommand::class);
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $application = new Application($kernel);

        $this->command = $application->find(ImportMemberPublishersListsCommand::COMMAND_NAME);

        $this->commandTester = new CommandTester($command);

        $this->removeFixtures();
    }

    /**
     * @test
     */
    public function it_executes_successfully(): void
    {
        // Arrange

        $this->commandTester->execute([
            'screen-name' => 'dev_obs'
        ]);

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
            TRUNCATE TABLE aggregate_subscription CASCADE;
        ');
    }
}
