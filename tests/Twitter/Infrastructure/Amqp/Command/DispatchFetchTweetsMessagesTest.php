<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Amqp\Command;

use App\Twitter\Infrastructure\Amqp\Command\DispatchFetchTweetsMessages;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationStrategyInterface;
use App\Twitter\Infrastructure\Amqp\MessageBus\PublicationMessageDispatcher;
use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\ApiAccessorBuilder;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group command
 */
class DispatchFetchTweetsMessagesTest extends KernelTestCase
{
    use ProphecyTrait;

    /**
     * @var Command
     */
    private Command $command;

    /**
     * @var CommandTester
     */
    private CommandTester $commandTester;

    /**
     * @test
     */
    public function it_dispatches_messages_to_fetch_member_statuses(): void
    {
        // Act

        $this->commandTester->execute(
            [
                CurationStrategyInterface::RULE_SCREEN_NAME => ApiAccessorBuilder::SCREEN_NAME,
                '--'.CurationStrategyInterface::RULE_LIST => ApiAccessorBuilder::LIST_NAME,
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

        $command = static::getContainer()->get(DispatchFetchTweetsMessages::class);
        $command->setPublicationMessageDispatcher($this->prophesizePublicationMessagerDispatcher());

        $application = new Application($kernel);

        $this->command = $application->find('app:dispatch-messages-to-fetch-member-tweets');

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @return object
     */
    private function prophesizePublicationMessagerDispatcher()
    {
        $publicationMessageDispatcherProphecy = $this->prophesize(PublicationMessageDispatcher::class);
        $publicationMessageDispatcherProphecy->dispatchPublicationMessages(
            Argument::type(CurationStrategyInterface::class),
            Argument::type(TokenInterface::class),
            Argument::cetera()
        );

        return $publicationMessageDispatcherProphecy->reveal();
    }
}