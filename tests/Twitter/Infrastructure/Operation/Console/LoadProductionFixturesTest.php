<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Operation\Console;

use App\Tests\Twitter\Infrastructure\Twitter\Api\Builder\ApiAccessorBuilder;
use App\Twitter\Infrastructure\Api\Entity\TokenType;
use App\Twitter\Infrastructure\Operation\Console\LoadProductionFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group production_fixtures
 */
class LoadProductionFixturesTest extends KernelTestCase
{
    /**
     * @var Command
     */
    private Command $command;

    /**
     * @var CommandTester
     */
    private CommandTester $commandTester;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        // Arrange

        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');

        $command = self::$container->get('test.'.LoadProductionFixtures::class);

        $application = new Application($kernel);

        $this->command = $application->find('devobs:load-production-fixtures');

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_loads_production_fixtures()
    {
        // Act

        $this->commandTester->execute([]);

        // Assert

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The status code of a command should be successful',
        );

        $tokenTypeRepository = $this->entityManager->getRepository(TokenType::class);
        $tokenTypes = $tokenTypeRepository->findAll();

        self::assertCount(
            2,
            $tokenTypes,
            'There should be two token types loaded in database by the command when needed.'
        );
    }
}