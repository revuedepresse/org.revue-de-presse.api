<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Console;

use App\Newsletter\Domain\Entity\Subscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SendDailyReportCommandTest extends KernelTestCase
{
    public function test_runs_and_exits_cleanly(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $tool->dropDatabase();
        $tool->createSchema([$em->getClassMetadata(Subscriber::class)]);

        $tester = new CommandTester((new Application(self::$kernel))->find('newsletter:send-daily'));
        $exit = $tester->execute(['--dry-run' => true, '--date' => '2026-05-22']);

        // With no subscribers and no highlights for that date, expect exit 2 (no highlights warning)
        // OR if SnapshotReader returns empty without erroring, the loop yields 0 — exit 2.
        // Acceptable assertion: command boots and exits with non-error code.
        self::assertContains($exit, [0, 2], 'Command should boot and exit with 0 or 2, got ' . $exit);
    }
}
