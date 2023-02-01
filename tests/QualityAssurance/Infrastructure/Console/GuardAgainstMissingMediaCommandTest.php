<?php
declare (strict_types=1);

namespace App\Tests\Membership\Infrastructure\Console;

use App\QualityAssurance\Infrastructure\Console\GuardAgainstMissingMediaCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group fetch_missing_media
 */
class GuardAgainstMissingMediaCommandTest extends KernelTestCase
{
    private GuardAgainstMissingMediaCommand $command;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        $command = static::getContainer()->get('test.'.GuardAgainstMissingMediaCommand::class);

        $application = new Application($kernel);

        $this->command = $application->find(GuardAgainstMissingMediaCommand::COMMAND_NAME);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_terminates_successfully()
    {
        // Act
        $this->commandTester->execute([GuardAgainstMissingMediaCommand::ARGUMENT_FILENAME => 'test.csv']);

        // Assert

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            sprintf(
                'When executing "%s" command, it status code should should be %d (for a successfully executed command)',
                $this->command::COMMAND_NAME,
                $this->command::SUCCESS
            )
        );

        $expectedOutput = <<<OUTPUT
About to get media located at https://pbs.twimg.com/profile_images/817042499134980096/LTpqSDMM_normal.jpg
About to collect tweet having id 1002929140973162498
Could not find tweet having id 1002929140973162498
About to get media located at https://pbs.twimg.com/profile_images/854315131395878912/jZceoVi9_normal.jpg
About to collect tweet having id 1002947636108955649
Could not find tweet having id 1002947636108955649
Processed successfully partition chunk #0

OUTPUT
;
        self::assertEquals(
            $this->commandTester->getDisplay(),
            $expectedOutput,
            sprintf(
                'When executing "%s" command, it should render fetched media locations and tweets not found ids.',
                $this->command::COMMAND_NAME
            )
        );
    }
}