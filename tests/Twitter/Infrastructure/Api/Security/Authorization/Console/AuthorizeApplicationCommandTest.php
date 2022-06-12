<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Api\Security\Authorization\Console;

use App\Membership\Domain\Model\MemberInterface;
use App\Tests\Twitter\Infrastructure\Api\Security\Authorization\Builder\AuthorizeAccessBuilder;
use App\Twitter\Domain\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Infrastructure\Api\Security\Authorization\Console\AuthorizeApplicationCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group api_access
 */
class AuthorizeApplicationCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    private EntityManagerInterface $entityManager;

    private AuthorizeApplicationCommand $testedCommand;

    private TokenRepositoryInterface $tokenRepository;

    private MemberRepositoryInterface $memberRepository;

    private string $consumerKey;

    private string $consumerSecret;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        /** @var AuthorizeApplicationCommand $command */
        $command = self::$container->get('test.'.AuthorizeApplicationCommand::class);

        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');

        $this->tokenRepository = self::$container->get('test.'.TokenRepositoryInterface::class);
        $this->memberRepository = self::$container->get('test.'.MemberRepositoryInterface::class);

        $this->consumerKey = self::$container->getParameter('twitter_api.consumer_key');
        $this->consumerSecret = self::$container->getParameter('twitter_api.consumer_secret');

        $application = new Application($kernel);

        $this->testedCommand = $application->find($command::COMMAND_NAME);

        $this->commandTester = new CommandTester($this->testedCommand);

        $this->removeFixtures();
    }

    /**
     * @test
     */
    public function it_grants_twitter_api_access_to_twitter_application(): void
    {
        $this->commandTester->setInputs(['1234']);

        $this->commandTester->execute(['command' => $this->testedCommand::COMMAND_NAME]);

        self::assertEquals(
            $this->testedCommand::SUCCESS,
            $this->commandTester->getStatusCode(),
            'The command should exit successfully.'
        );

        $display = $this->commandTester->getDisplay();

        self::assertStringContainsString(
            AuthorizeAccessBuilder::AUTHORIZATION_URL,
            $display,
            'The command should render a clickable authorization URL.'
        );

        self::assertStringContainsString(
            'This Twitter application has been granted access to Twitter API on your behalf.',
            $display,
            'The command should acknowledge the authorization.'
        );

        $token = $this->tokenRepository->findOneBy(['oauthToken' => AuthorizeAccessBuilder::ACCESS_TOKEN]);

        self::assertInstanceOf(
            TokenInterface::class,
            $token,
            'An access token should have saved.'
        );

        self::assertEquals(
            $this->consumerKey,
            $token->getConsumerKey(),
            'The application consumer key should be saved.'
        );

        self::assertEquals(
            $this->consumerSecret,
            $token->getConsumerSecret(),
            'The application consumer secret should be saved.'
        );

        self::assertEquals(
            AuthorizeAccessBuilder::ACCESS_TOKEN,
            $token->getAccessToken(),
            'The access token should be saved.'
        );

        self::assertEquals(
            AuthorizeAccessBuilder::ACCESS_TOKEN_SECRET,
            $token->getAccessTokenSecret(),
            'The access token secret should be saved.'
        );

        $expectedScreenName = AuthorizeAccessBuilder::SCREEN_NAME;

        /** @var MemberInterface $member */
        $member = $this->memberRepository->findOneBy(['twitter_username' => $expectedScreenName]);

        self::assertInstanceOf(
            MemberInterface::class,
            $member,
            'A member should be saved.'
        );

        self::assertEquals(
            $expectedScreenName,
            $member->twitterScreenName(),
            sprintf('The member should have the expected Twitter screen name ("%s")', $expectedScreenName)
        );

        $expectedTwitterId = AuthorizeAccessBuilder::USER_ID;
        self::assertEquals(
            $expectedTwitterId,
            $member->twitterId(),
            sprintf('The member should have the expected Twitter id (#%s)', $expectedTwitterId)
        );

        $tokens = $member->getTokens();

        self::assertCount(
            1,
            $tokens,
            sprintf('The member should be related to exactly one token.')
        );

        self::assertEquals(
            $token,
            $tokens[0],
            sprintf('The member should be related to the newly created token.')
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
            TRUNCATE TABLE member_aggregate_subscription CASCADE;
        ');
        $this->entityManager->getConnection()->executeQuery('
            TRUNCATE TABLE weaving_user_token;
        ');
        $this->entityManager->getConnection()->executeQuery('
            TRUNCATE TABLE weaving_access_token CASCADE;
        ');
        $this->entityManager->getConnection()->executeQuery('
            TRUNCATE TABLE weaving_token_type CASCADE;
        ');
        $this->entityManager->getConnection()->executeQuery('
            TRUNCATE TABLE weaving_user CASCADE;
        ');
    }
}
