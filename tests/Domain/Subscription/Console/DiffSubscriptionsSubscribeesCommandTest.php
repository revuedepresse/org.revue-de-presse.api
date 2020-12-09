<?php
declare (strict_types=1);

namespace App\Tests\Domain\Subscription\Console;

use App\Domain\Subscription\Console\DiffSubscriptionsSubscribeesCommand;
use App\Tests\Builder\Infrastructure\Collection\Repository\FollowersListCollectedEventRepositoryBuilder;
use App\Tests\Builder\Infrastructure\Collection\Repository\FriendsListCollectedEventRepositoryBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group diffing
 */
class DiffSubscriptionsSubscribeesCommandTest extends KernelTestCase
{
    private DiffSubscriptionsSubscribeesCommand $command;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        /** @var DiffSubscriptionsSubscribeesCommand $command */
        $command = self::$container->get('test.'.DiffSubscriptionsSubscribeesCommand::class);

        $application = new Application($kernel);

        $this->command = $application->find('press-review:diff-subscriptions-subscribees');
        $this->command->setSubscriptionsRepository(FriendsListCollectedEventRepositoryBuilder::make());
        $this->command->setSubscribeesRepository(FollowersListCollectedEventRepositoryBuilder::make());

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_diffs_subscriptions_and_subscribees(): void
    {
        $this->commandTester->execute(['screen_name' => 'thierrymarianne']);

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The status code of a command should be successful',
        );
    }
}