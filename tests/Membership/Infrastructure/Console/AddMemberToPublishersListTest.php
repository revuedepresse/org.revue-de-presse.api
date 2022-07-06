<?php
declare (strict_types=1);

namespace App\Tests\Membership\Infrastructure\Console;

use App\Membership\Infrastructure\Console\AddMembersBatchToListCommand;
use App\Tests\Twitter\Infrastructure\Http\Builder\Client\ListAwareHttpClientBuilder;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group member_subscription
 */
class AddMemberToPublishersListTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private AddMembersBatchToListCommand $command;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        $command = static::getContainer()->get('test.'.AddMembersBatchToListCommand::class);

        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $application = new Application($kernel);

        $this->command = $application->find(AddMembersBatchToListCommand::COMMAND_NAME);

        $this->commandTester = new CommandTester($command);

        $this->removeFixtures();
    }

    /**
     * @test
     */
    public function it_adds_a_member_to_a_twitter_list(): void
    {
        $targetPublishersList = ListAwareHttpClientBuilder::LIST_NAME;
        $memberScreenName = 'johndoe';

        // Act
        $this->commandTester->execute([
            '--'.$this->command::OPTION_MEMBER_LIST => $memberScreenName,
            '--'.$this->command::OPTION_PUBLISHERS_LIST_NAME => $targetPublishersList,
            $this->command::ARGUMENT_SCREEN_NAME => 'dev_obs',
        ]);

        // Assert
        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The return code of this command execution should be unsuccessful.',
        );

        $publishersListRepository = $this->entityManager->getRepository(PublishersList::class);
        $publishersList = $publishersListRepository->findOneBy([
            'name' => $targetPublishersList,
            'screenName' => $memberScreenName
        ]);

        self::assertInstanceOf(
            PublishersListInterface::class,
            $publishersList,
            sprintf(
                'There should be a Twitter list in the database having name "%s" for member "%s"',
                $targetPublishersList,
                $memberScreenName
            )
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
            TRUNCATE TABLE publishers_list CASCADE;
        ');
        $this->entityManager->getConnection()->executeQuery('
            ALTER SEQUENCE publishers_list_id_seq restart; 
        ');
        $this->entityManager->getConnection()->executeQuery('
            UPDATE publishers_list set id = DEFAULT;
        ');
        $this->entityManager->getConnection()->executeQuery('
            TRUNCATE TABLE weaving_user CASCADE;
        ');
        $this->entityManager->getConnection()->executeQuery('
            ALTER SEQUENCE weaving_user_usr_id_seq restart; 
        ');
        $this->entityManager->getConnection()->executeQuery('
            UPDATE weaving_user set usr_id = DEFAULT;
        ');
    }
}