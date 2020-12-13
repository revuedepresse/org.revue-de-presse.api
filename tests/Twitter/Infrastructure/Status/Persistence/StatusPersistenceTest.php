<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Status\Persistence;

use App\Twitter\Infrastructure\Api\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Api\Entity\ArchivedStatus;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Domain\Publication\Repository\TaggedStatusRepositoryInterface;
use App\Twitter\Domain\Publication\StatusCollection;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Domain\Publication\TaggedStatus;
use App\Twitter\Infrastructure\Publication\Persistence\StatusPersistence;
use App\Twitter\Infrastructure\Publication\Persistence\StatusPersistenceInterface;
use App\Twitter\Infrastructure\Operation\Collection\CollectionInterface;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function count;

/**
 * @package App\Tests\Twitter\Infrastructure\Persistence
 * @group persistence
 */
class StatusPersistenceTest extends KernelTestCase
{
    private const ARCHIVE_STATUS_HASH = 'b90db10b2ac5a0a399886d677ef0c11200a6f2c5';

    private StatusPersistenceInterface $statusPersistence;

    public function setUp(): void
    {
        parent::setUp();

        self::$kernel = self::bootKernel();
        self::$container = self::$kernel->getContainer();

        $this->removeUnarchivedStatus();

        /** @var StatusPersistence $statusPersistence */
        $this->statusPersistence = self::$container->get(StatusPersistence::class);
    }

    protected function tearDown(): void
    {
        $this->removeUnarchivedStatus();

        parent::tearDown();
    }

    /**
     * @test
     *
     * @throw
     */
    public function it_should_not_persist_any_status_when_passing_an_empty_collection(): void
    {
        // Arrange

        /** @var StatusPersistence $statusPersistence */
        $this->statusPersistence = self::$container->get(StatusPersistence::class);

        // Act

        $normalizedStatus = $this->statusPersistence->persistAllStatuses(
            [],
            new AccessToken('ident-210290dqlpfoamow')
        );

        // Assert

        self::assertArrayHasKey('normalized_status', $normalizedStatus);
        self::assertArrayHasKey('screen_name', $normalizedStatus);
        self::assertArrayHasKey('status', $normalizedStatus);

        self::assertInstanceOf(StatusCollection::class, $normalizedStatus['status']);
        self::assertCount(0, $normalizedStatus['status']->toArray());
        self::assertCount(0, $normalizedStatus['normalized_status']);
    }

    /**
     * @test
     *
     * @throws
     */
    public function it_should_persist_a_status_for_a_single_item_collection(): void
    {
        // Arrange

        $createdAt = $this->makePublicationDate();
        $statusProperties = $this->makeStatusProperties($createdAt);
        $allStatuses = [(object) $statusProperties];

        // Act

        $normalizedStatus = $this->statusPersistence->persistAllStatuses(
            $allStatuses,
            new AccessToken('ident-210290dqlpfoamow')
        );

        // Assert

        self::assertArrayHasKey('normalized_status', $normalizedStatus);
        self::assertArrayHasKey('screen_name', $normalizedStatus);
        self::assertArrayHasKey('status', $normalizedStatus);

        self::assertInstanceOf(
            CollectionInterface::class,
            $normalizedStatus['normalized_status']
        );
        self::assertCount(
            count($allStatuses),
            $normalizedStatus['normalized_status']
        );

        $taggedStatus = $normalizedStatus['normalized_status']->first();
        self::assertInstanceOf(
            TaggedStatus::class,
            $normalizedStatus['normalized_status']->first()
        );

        /** @var TaggedStatus $taggedStatus */
        self::assertEquals(
            $statusProperties['user']->screen_name,
            $taggedStatus->screenName()
        );
        self::assertEquals(
            $statusProperties['user']->name,
            $taggedStatus->name()
        );
        self::assertEquals(
            $statusProperties['user']->profile_image_url,
            $taggedStatus->avatarUrl()
        );
        self::assertEquals(
            $statusProperties['full_text'],
            $taggedStatus->text()
        );
        self::assertEquals(
            $createdAt,
            $taggedStatus->publishedAt()
        );

        self::assertInstanceOf(
            StatusCollection::class,
            $normalizedStatus['status']
        );
        self::assertCount(
            count($allStatuses),
            $normalizedStatus['status']->toArray()
        );
        self::assertNotNull(
            $normalizedStatus['status']->first()
        );
        self::assertInstanceOf(
            Status::class,
            $normalizedStatus['status']->first()
        );
    }

    /**
     * @test
     *
     * @throws
     */
    public function it_should_unarchive_an_archived_status(): void
    {
        // Arrange

        $createdAt = $this->makePublicationDate();

        $taggedStatusRepository = $this->prophesize(TaggedStatusRepositoryInterface::class);

        $archivedStatus = (new ArchivedStatus())
            ->setUserAvatar('https://gravatar.com/bobmarley')
            ->setName('Bob Marley')
            ->setScreenName('bobm')
            ->setHash(sha1('This is a tweet published by an artist.'.'43'))
            ->setIdentifier('ident-210290dqlpfoamow')
            ->setApiDocument('{}')
            ->setStatusId('43')
            ->setText('This is a tweet published by an artist.')
            ->setIndexed(false)
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($createdAt);

        /** @var TaggedStatusRepositoryInterface|ObjectProphecy $taggedStatusRepository */
        $taggedStatusRepository->convertPropsToStatus(
            Argument::type('array'),
            Argument::cetera()
        )->willReturn($archivedStatus);

        $this->statusPersistence->setTaggedStatusRepository($taggedStatusRepository->reveal());

        $statusProperties = $this->makeStatusProperties($createdAt);
        $allStatuses = [(object) $statusProperties];

        // Act

        $normalizedStatus = $this->statusPersistence->persistAllStatuses(
            $allStatuses,
            new AccessToken('ident-210290dqlpfoamow')
        );

        // Assert

        self::assertInstanceOf(
            StatusCollection::class,
            $normalizedStatus['status']
        );
        self::assertCount(
            count($allStatuses),
            $normalizedStatus['status']->toArray()
        );
        self::assertNotNull(
            $normalizedStatus['status']->first()
        );

        $unarchivedStatus = $normalizedStatus['status']->first();
        self::assertInstanceOf(
            Status::class,
            $unarchivedStatus
        );
        self::assertEquals(
            $archivedStatus->getName(),
            $unarchivedStatus->getName()
        );
        self::assertEquals(
            $archivedStatus->getScreenName(),
            $unarchivedStatus->getScreenName()
        );
        self::assertEquals(
            $archivedStatus->getHash(),
            $unarchivedStatus->getHash()
        );
        self::assertEquals(
            $archivedStatus->getText(),
            $unarchivedStatus->getText()
        );
        self::assertEquals(
            $archivedStatus->getUserAvatar(),
            $unarchivedStatus->getUserAvatar()
        );
        self::assertEquals(
            $archivedStatus->getApiDocument(),
            $unarchivedStatus->getApiDocument()
        );
        self::assertEquals(
            $archivedStatus->getStatusId(),
            $unarchivedStatus->getStatusId()
        );
        self::assertEquals(
            $archivedStatus->getIdentifier(),
            $unarchivedStatus->getIdentifier()
        );
        self::assertEquals(
            $archivedStatus->getCreatedAt(),
            $unarchivedStatus->getCreatedAt()
        );
        self::assertEquals(
            $archivedStatus->getUpdatedAt(),
            $unarchivedStatus->getUpdatedAt()
        );
        self::assertEquals(
            $archivedStatus->getIndexed(),
            $unarchivedStatus->getIndexed()
        );
    }

    /**
     * @param DateTimeInterface $publicationDate
     * @return array
     * @throws Exception
     */
    private function makeStatusProperties(DateTimeInterface $publicationDate): array
    {
        return [
            'user' => (object)[
                'screen_name' => 'mariec',
                'name' => 'Marie Curie',
                'profile_image_url' => 'https://gravatar.com/image42.jpeg'
            ],
            'id_str' => '42',
            'full_text' => 'I am a longer publication.',
            'text' => 'I am a publication.',
            'created_at' => $publicationDate->format(DateTimeInterface::RFC7231),
        ];
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    private function makePublicationDate(): DateTime
    {
        return new DateTime(
            (new DateTime(
                'now',
                new DateTimeZone('Europe/Paris')
            ))->format(DateTimeInterface::RFC7231)
        );
    }

    private function removeUnarchivedStatus(): void
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $statusRepository = $entityManager->getRepository('App\Twitter\Infrastructure\Api\Entity\Status');
        $status = $statusRepository->findOneBy(['hash' => self::ARCHIVE_STATUS_HASH]);
        if ($status instanceof StatusInterface) {
            $entityManager->remove($status);
            $entityManager->flush();
        }
    }
}
