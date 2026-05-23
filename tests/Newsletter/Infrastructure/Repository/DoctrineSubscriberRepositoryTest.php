<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Repository;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;
use App\Newsletter\Infrastructure\Doctrine\BootEncryptedStringType;
use App\Newsletter\Infrastructure\Repository\DoctrineSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

final class DoctrineSubscriberRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DoctrineSubscriberRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        // Force BootEncryptedStringType instantiation so EncryptedStringType::injectKey()
        // is called. The tagged listener fires on kernel.request / console.command but
        // NOT on KernelTestCase::bootKernel(), so we pull the service explicitly here.
        self::getContainer()->get(BootEncryptedStringType::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($this->em);
        $tool->dropDatabase();
        $tool->createSchema([$this->em->getClassMetadata(Subscriber::class)]);
        $this->repo = new DoctrineSubscriberRepository($this->em);
    }

    public function test_save_and_find_by_email_hash(): void
    {
        $email = EmailAddress::fromString('alice@example.com');
        $sub = Subscriber::enrol(
            new Ulid(),
            $email,
            OpaqueToken::fromRawBytes(random_bytes(32)),
            new \DateTimeImmutable('+7 days'),
            OpaqueToken::fromRawBytes(random_bytes(32)),
            'rdp-api',
            new \DateTimeImmutable(),
        );
        $this->repo->save($sub);
        $this->em->clear();

        $found = $this->repo->findByEmailHash($email->hash());
        self::assertNotNull($found);
        self::assertSame($email->hash(), $found->emailHash());
    }

    public function test_iterate_active_yields_only_active_rows(): void
    {
        $this->seedSubscriber('alice@example.com', confirm: true);
        $this->seedSubscriber('bob@example.com', confirm: false);
        $this->em->clear();

        $rows = iterator_to_array($this->repo->iterateActive(10));
        self::assertCount(1, $rows);
        self::assertSame(SubscriberStatus::Active, $rows[0]->status());
    }

    private function seedSubscriber(string $emailRaw, bool $confirm): Subscriber
    {
        $email = EmailAddress::fromString($emailRaw);
        $confirmToken = OpaqueToken::fromRawBytes(random_bytes(32));
        $sub = Subscriber::enrol(
            new Ulid(),
            $email,
            $confirmToken,
            new \DateTimeImmutable('+7 days'),
            OpaqueToken::fromRawBytes(random_bytes(32)),
            'rdp-api',
            new \DateTimeImmutable(),
        );
        if ($confirm) {
            $sub->confirm($confirmToken, new \DateTimeImmutable());
        }
        $this->repo->save($sub);
        return $sub;
    }
}
