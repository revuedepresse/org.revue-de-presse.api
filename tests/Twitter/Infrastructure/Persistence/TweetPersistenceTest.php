<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Persistence;

use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Http\Entity\ArchivedTweet;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Domain\Publication\Repository\TaggedTweetRepositoryInterface;
use App\Twitter\Infrastructure\Publication\Dto\StatusCollection;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Publication\Dto\TaggedTweet;
use App\Twitter\Infrastructure\Persistence\TweetPersistenceLayer;
use App\Twitter\Domain\Persistence\TweetPersistenceLayerInterface;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function count;

/**
 * @package App\Tests\Twitter\Infrastructure\Persistence
 * @group persistence
 */
class StatusPersistenceTest extends KernelTestCase
{
    use ProphecyTrait;

    private const ARCHIVE_STATUS_HASH = 'b90db10b2ac5a0a399886d677ef0c11200a6f2c5';

    private TweetPersistenceLayerInterface $persistenceLayer;

    public function setUp(): void
    {
        parent::setUp();

        self::$kernel = self::bootKernel();

        $this->removeUnarchivedStatus();

        /** @var TweetPersistenceLayer $statusPersistence */
        $this->persistenceLayer = static::getContainer()->get(TweetPersistenceLayer::class);
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

        /** @var TweetPersistenceLayer $statusPersistence */
        $this->persistenceLayer = static::getContainer()->get(TweetPersistenceLayer::class);

        // Act

        $normalizedStatus = $this->persistenceLayer->persistTweetsCollection(
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

        $normalizedStatus = $this->persistenceLayer->persistTweetsCollection(
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

        $taggedTweet = $normalizedStatus['normalized_status']->first();
        self::assertInstanceOf(
            TaggedTweet::class,
            $normalizedStatus['normalized_status']->first()
        );

        /** @var TaggedTweet $taggedTweet */
        self::assertEquals(
            $statusProperties['user']->screen_name,
            $taggedTweet->screenName()
        );
        self::assertEquals(
            $statusProperties['user']->name,
            $taggedTweet->name()
        );
        self::assertEquals(
            $statusProperties['user']->profile_image_url,
            $taggedTweet->avatarUrl()
        );
        self::assertEquals(
            $statusProperties['full_text'],
            $taggedTweet->text()
        );
        self::assertEquals(
            $createdAt,
            $taggedTweet->publishedAt()
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
            Tweet::class,
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

        $TaggedTweetRepository = $this->prophesize(TaggedTweetRepositoryInterface::class);

        $archivedStatus = (new ArchivedTweet())
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

        /** @var TaggedTweetRepositoryInterface|ObjectProphecy $TaggedTweetRepository */
        $TaggedTweetRepository->convertPropsToStatus(
            Argument::type('array'),
            Argument::cetera()
        )->willReturn($archivedStatus);

        $this->persistenceLayer->setTaggedTweetRepository($TaggedTweetRepository->reveal());

        $statusProperties = $this->makeStatusProperties($createdAt);
        $allStatuses = [(object) $statusProperties];

        // Act

        $normalizedStatus = $this->persistenceLayer->persistTweetsCollection(
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
            Tweet::class,
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
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $statusRepository = $entityManager->getRepository('App\Twitter\Infrastructure\Http\Entity\Tweet');
        $status = $statusRepository->findOneBy(['hash' => self::ARCHIVE_STATUS_HASH]);
        if ($status instanceof TweetInterface) {
            $entityManager->remove($status);
            $entityManager->flush();
        }
    }
}
