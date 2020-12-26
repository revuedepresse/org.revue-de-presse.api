<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Operation\Console;

use App\Membership\Infrastructure\Entity\Legacy\Member;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Api\Entity\TokenType;
use App\Twitter\Infrastructure\Operation\Console\LoadProductionFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group production_fixtures
 */
class LoadProductionFixturesTest extends KernelTestCase
{
    private const API_ACCESS_TOKEN = '___sec___access__token__';

    private Command $command;

    private CommandTester $commandTester;

    private EntityManagerInterface $entityManager;

    private ObjectRepository $tokenTypeRepository;

    private ObjectRepository $tokenRepository;

    private MemberRepositoryInterface $memberRepository;

    protected function setUp(): void
    {
        // Arrange

        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');
        $this->tokenTypeRepository = $this->entityManager->getRepository(TokenType::class);
        $this->tokenRepository = $this->entityManager->getRepository(Token::class);
        $this->memberRepository = $this->entityManager->getRepository(Member::class);

        $this->removeExistingFixtures();

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

        $this->commandTester->execute([
            LoadProductionFixtures::ARGUMENT_USER_TOKEN => 'token',
            LoadProductionFixtures::ARGUMENT_USER_SECRET => 'secret',
            LoadProductionFixtures::ARGUMENT_CONSUMER_KEY => 'consumer_key',
            LoadProductionFixtures::ARGUMENT_CONSUMER_SECRET => 'consumer_secret',
            LoadProductionFixtures::ARGUMENT_API_ACCESS_TOKEN => self::API_ACCESS_TOKEN,
        ]);

        // Assert

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The status code of a command should be successful',
        );

        $tokenTypes = $this->tokenTypeRepository->findAll();
        $token = $this->tokenRepository->findAll();
        $apiConsumingMembers = $this->memberRepository->findBy(['apiKey' => self::API_ACCESS_TOKEN]);

        self::assertCount(
            2,
            $tokenTypes,
            'There should be two token types available from the database by the command when needed.'
        );

        self::assertCount(
            1,
            $token,
            'There should be one least a token available from the database.'
        );

        self::assertInstanceOf(
            Token::class,
            $token[0],
            sprintf('A token should be an instance of "%s"', Token::class)
        );

        /** @var Token $token */
        $token = $token[0];

        self::assertEquals(
            'token',
            $token->getOAuthToken(),
            'The oauth token property of the token should be the user token argument value of the command'
        );
        self::assertEquals(
            'secret',
            $token->getOAuthSecret(),
            'The oauth secret property of the token should be the user secret argument value of the command'
        );
        self::assertEquals(
            'consumer_key',
            $token->getConsumerKey(),
            'The consumer key property of the token should be the consumer key argument value of the command'
        );
        self::assertEquals(
            'consumer_secret',
            $token->getConsumerSecret(),
            'The consumer secret property of the token should be the consumer secret argument value of the command'
        );

        self::assertCount(
            1,
            $apiConsumingMembers,
            'There should be exactly one member able to consume the API'
        );
    }

    protected function tearDown(): void
    {
        $this->removeExistingFixtures();

        parent::tearDown();
    }

    private function removeExistingFixtures(): void
    {
        $this->entityManager->getConnection()->executeQuery('
            DELETE FROM weaving_access_token;
        ');
        $this->entityManager->getConnection()->executeQuery('
            DELETE FROM weaving_token_type;
        ');
        $this->entityManager->getConnection()->executeQuery('
            DELETE FROM weaving_user;
        ');
    }
}