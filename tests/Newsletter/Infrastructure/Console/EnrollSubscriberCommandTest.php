<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Console;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Infrastructure\Doctrine\BootEncryptedStringType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class EnrollSubscriberCommandTest extends KernelTestCase
{
    public function test_enrol_creates_pending_row(): void
    {
        self::bootKernel();
        self::getContainer()->get(BootEncryptedStringType::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $tool->dropDatabase();
        $tool->createSchema([$em->getClassMetadata(Subscriber::class)]);

        $tester = new CommandTester((new Application(self::$kernel))->find('newsletter:enroll'));
        $exit = $tester->execute(['email' => 'alice@example.com']);

        self::assertSame(0, $exit);
        self::assertStringContainsString('created', $tester->getDisplay());
    }
}
