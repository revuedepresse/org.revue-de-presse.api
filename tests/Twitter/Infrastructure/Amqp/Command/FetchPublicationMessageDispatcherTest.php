<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Amqp\Command;

use App\Twitter\Infrastructure\Amqp\Command\FetchPublicationMessageDispatcher;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Curation\PublicationStrategyInterface;
use App\Twitter\Infrastructure\Amqp\MessageBus\PublicationMessageDispatcher;
use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\ApiAccessorBuilder;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group command
 */
class FetchPublicationMessageDispatcherTest extends KernelTestCase
{
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
                PublicationStrategyInterface::RULE_SCREEN_NAME => ApiAccessorBuilder::SCREEN_NAME,
                '--'.PublicationStrategyInterface::RULE_LIST => ApiAccessorBuilder::LIST_NAME,
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

        self::$container = $kernel->getContainer();

        $command = self::$container->get(FetchPublicationMessageDispatcher::class);
        $command->setPublicationMessageDispatcher($this->prophesizePublicationMessagerDispatcher());

        $application = new Application($kernel);

        $this->command = $application->find('devobs:dispatch-messages-to-fetch-member-statuses');

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @return object
     */
    private function prophesizePublicationMessagerDispatcher()
    {
        $publicationMessageDispatcherProphecy = $this->prophesize(PublicationMessageDispatcher::class);
        $publicationMessageDispatcherProphecy->dispatchPublicationMessages(
            Argument::type(PublicationStrategyInterface::class),
            Argument::type(TokenInterface::class),
            Argument::cetera()
        );

        return $publicationMessageDispatcherProphecy->reveal();
    }
}
