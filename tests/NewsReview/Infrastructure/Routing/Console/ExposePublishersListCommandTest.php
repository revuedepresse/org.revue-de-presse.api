<?php

declare (strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Routing\Console;

use App\NewsReview\Domain\Routing\Repository\PublishersListRouteRepositoryInterface;
use App\NewsReview\Infrastructure\Routing\Console\ExposePublishersListCommand;
use App\NewsReview\Infrastructure\Routing\Entity\PublishersListRoute;
use App\Twitter\Domain\Publication\Repository\PublishersListRepositoryInterface;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group publishers_list
 */
class ExposePublishersListCommandTest extends KernelTestCase
{
    private const PUBLISHERS_LIST_NAME = 'programming :: PHP';
    private const PUBLISHERS_LIST_ID = '1';
    private const HOSTNAME = 'php.watch.dev';
    private ExposePublishersListCommand $commandUnderTest;

    private EntityManagerInterface $entityManager;

    private PublishersListRepositoryInterface $publishersListRepository;

    private PublishersListRouteRepositoryInterface $publishersListRouteRepository;

    private CommandTester $commandTester;

    public function setUp(): void
    {
        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        /** @var ExposePublishersListCommand $command */
        $command = self::$container->get('test.'.ExposePublishersListCommand::class);

        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');
        $this->publishersListRepository = $this->entityManager->getRepository(PublishersList::class);
        $this->publishersListRouteRepository = $this->entityManager->getRepository(PublishersListRoute::class);

        $application = new Application($kernel);

        $this->commandUnderTest = $application->find(ExposePublishersListCommand::COMMAND_NAME);

        $this->commandTester = new CommandTester($command);

        $this->removeFixtures();
    }

    /**
     * @test
     */
    public function it_can_not_expose_a_non_existent_publishers_list(): void
    {
        // Arrange
        // See ->setUp()

        // Act

        $this->commandTester->execute([
            $this->commandUnderTest::ARGUMENT_PUBLISHERS_LIST_NAME => self::PUBLISHERS_LIST_NAME,
            $this->commandUnderTest::ARGUMENT_HOSTNAME => self::HOSTNAME,
        ]);

        // Assert

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->commandUnderTest::FAILURE,
            'The return code of this command execution should be unsuccessful.',
        );

        self::assertEquals(
            $this->commandTester->getDisplay(),
            sprintf(
                'No publishers list having name "%s" has been found.'.PHP_EOL,
                self::PUBLISHERS_LIST_NAME
            ),
            'The command display should be informative about the non-existent publishers list.'
        );
    }

    /**
     * @test
     */
    public function it_exposes_a_publishers_list_from_a_domain(): void
    {
        // Arrange
        $publishersList = $this->publishersListRepository->byName('dev_obs', self::PUBLISHERS_LIST_NAME, self::PUBLISHERS_LIST_ID);
        // See also ->setUp()

        // Act

        $this->commandTester->execute([
            $this->commandUnderTest::ARGUMENT_PUBLISHERS_LIST_NAME => $publishersList->name(),
            $this->commandUnderTest::ARGUMENT_HOSTNAME => self::HOSTNAME,
        ]);

        // Assert

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->commandUnderTest::SUCCESS,
            'The status code of this command should be successful',
        );

        $route = $this->publishersListRouteRepository->findOneBy([
            'hostname' => self::HOSTNAME,
            'publicId' => $publishersList->publicId()
        ]);

        self::assertInstanceOf(
            PublishersListRoute::class,
            $route,
            sprintf(
                'A newly exposed route should be an instance of "%s"',
                PublishersListRoute::class
            )
        );
    }

    /**
     * @test
     */
    public function it_can_not_expose_the_same_publishers_list_from_the_same_domain_twice(): void
    {
        // Arrange
        $publishersList = $this->publishersListRepository->byName('dev_obs', self::PUBLISHERS_LIST_NAME, self::PUBLISHERS_LIST_ID);
        // See also ->setUp()

        // Act

        $this->commandTester->execute([
            $this->commandUnderTest::ARGUMENT_PUBLISHERS_LIST_NAME => $publishersList->name(),
            $this->commandUnderTest::ARGUMENT_HOSTNAME => self::HOSTNAME,
        ]);

        $this->commandTester->execute([
            $this->commandUnderTest::ARGUMENT_PUBLISHERS_LIST_NAME => $publishersList->name(),
            $this->commandUnderTest::ARGUMENT_HOSTNAME => self::HOSTNAME,
        ]);

        // Assert

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->commandUnderTest::FAILURE,
            'The return code of this command execution should be unsuccessful.',
        );

        self::assertEquals(
            $this->commandTester->getDisplay(),
            sprintf(
                'A route has already been exposed for publishers list "%s".'.PHP_EOL,
                self::PUBLISHERS_LIST_NAME
            ),
            'The command display should be informative about a publisher list, 
            which is already exposed from a domain.'
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
            TRUNCATE TABLE publishers_list_route CASCADE;
        ');
    }
}
