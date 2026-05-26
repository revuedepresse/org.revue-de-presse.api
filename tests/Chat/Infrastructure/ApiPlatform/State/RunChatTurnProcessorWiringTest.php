<?php
declare(strict_types=1);

namespace App\Tests\Chat\Infrastructure\ApiPlatform\State;

use App\Chat\Infrastructure\ApiPlatform\State\RunChatTurnProcessor;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Regression guard for the services.chat.yaml DI declaration.
 *
 * The file used to declare RunChatTurnProcessor with only `tags:`, no
 * `arguments:`, and no `_defaults` block to inherit autowire from — so
 * Symfony compiled the service as `new RunChatTurnProcessor()` (zero args)
 * and the first request to POST /api/chat/turns 500'd with:
 *
 *   Too few arguments to function
 *   App\Chat\Infrastructure\ApiPlatform\State\RunChatTurnProcessor::__construct(),
 *   0 passed in .../getRunChatTurnProcessorService.php and exactly 2 expected
 *
 * Resolving the service from the test container exercises the same compiled
 * factory; if autowire is lost again, this test throws ArgumentCountError.
 */
final class RunChatTurnProcessorWiringTest extends KernelTestCase
{
    public function testProcessorIsConstructedWithItsCollaboratorsByTheContainer(): void
    {
        self::bootKernel();

        $processor = self::getContainer()->get(RunChatTurnProcessor::class);

        self::assertInstanceOf(RunChatTurnProcessor::class, $processor);
    }
}
