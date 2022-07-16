<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Amqp\Console;

use App\Tests\Twitter\Infrastructure\Http\Builder\Client\HttpClientBuilder;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Infrastructure\Amqp\Console\FetchTweetsAmqpMessagesDispatcherCommand;
use App\Twitter\Infrastructure\Amqp\MessageBus\FetchTweetsAmqpMessagesDispatcher;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group command
 * @group dispatch_amqp_messages
 */
class FetchTweetsAmqpMessagesDispatcherTest extends KernelTestCase
{
    use ProphecyTrait;

    private Command $command;

    private CommandTester $commandTester;

    /**
     * @test
     */
    public function it_dispatches_messages_to_fetch_member_statuses(): void
    {
        // Act

        $this->commandTester->execute(
            [
                CurationRulesetInterface::RULE_SCREEN_NAME => HttpClientBuilder::SCREEN_NAME,
                '--'.CurationRulesetInterface::RULE_LIST => HttpClientBuilder::LIST_NAME,
            ]
        );

        // Assert

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The return code of this command execution should be successful.',
        );
    }

    protected function setUp(): void
    {
        // Arrange
        $kernel = static::bootKernel();

        /** @var FetchTweetsAmqpMessagesDispatcherCommand $command */
        $command = static::getContainer()->get(FetchTweetsAmqpMessagesDispatcherCommand::class);
        $command->setFetchTweetsAmqpMessagesDispatcher($this->prophesizePublicationMessagerDispatcher());

        $application = new Application($kernel);

        $this->command = $application->find('app:dispatch-fetch-tweets-amqp-messages');

        $this->commandTester = new CommandTester($command);
    }

    private function prophesizePublicationMessagerDispatcher()
    {
        /** @var FetchTweetsAmqpMessagesDispatcher $fetchTweetsAmqpMessagesDispatcherProphecy */
        $fetchTweetsAmqpMessagesDispatcherProphecy = $this->prophesize(FetchTweetsAmqpMessagesDispatcher::class);
        $fetchTweetsAmqpMessagesDispatcherProphecy->dispatchFetchTweetsMessages(
            Argument::type(CurationRulesetInterface::class),
            Argument::type(TokenInterface::class),
            Argument::cetera()
        );

        return $fetchTweetsAmqpMessagesDispatcherProphecy->reveal();
    }
}
