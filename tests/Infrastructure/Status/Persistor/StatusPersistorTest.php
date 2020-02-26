<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Persistor;

use App\Api\AccessToken\AccessToken;
use App\Domain\Status\TaggedStatus;
use App\Infrastructure\Status\Persistor\StatusPersistor;
use App\Operation\Collection\CollectionInterface;
use DateTime;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function count;

/**
 * @package App\Tests\Infrastructure\Persistor
 * @group persistence
 */
class StatusPersistorTest extends KernelTestCase
{
    /**
     * @test
     *
     * @throw
     */
    public function it_should_not_persist_any_status_when_passing_an_empty_collection(): void
    {
        self::$kernel = self::bootKernel();
        self::$container = self::$kernel->getContainer();

        /** @var StatusPersistor $statusPersistor */
        $statusPersistor = self::$container->get(StatusPersistor::class);
        $normalizedStatus = $statusPersistor->persistAllStatuses(
            [],
            new AccessToken('ident-210290dqlpfoamow')
        );

        self::assertArrayHasKey('extracts', $normalizedStatus);
        self::assertArrayHasKey('screen_name', $normalizedStatus);
        self::assertArrayHasKey('statuses', $normalizedStatus);
    }

    /**
     * @test
     *
     * @throws
     */
    public function it_should_persist_a_status_for_a_single_item_collection(): void
    {
        self::$kernel = self::bootKernel();
        self::$container = self::$kernel->getContainer();

        /** @var StatusPersistor $statusPersistor */
        $statusPersistor = self::$container->get(StatusPersistor::class);

        $createdAt = new DateTime(
            (new DateTime(
                'now',
                new \DateTimeZone('Europe/Paris')
            ))->format(DateTimeInterface::RFC7231)
        );
        $statusProperties = [
            'user' => (object) [
                'screen_name' => 'mariec',
                'name' => 'Marie Curie',
                'profile_image_url' => 'https://gravatar.com/image42.jpeg'
            ],
            'id_str' => '42',
            'full_text' => 'I am a longer publication.',
            'text' => 'I am a publication.',
            'created_at' => $createdAt->format(DateTimeInterface::RFC7231),
        ];
        $allStatuses = [(object) $statusProperties];
        $normalizedStatus = $statusPersistor->persistAllStatuses(
            $allStatuses,
            new AccessToken('ident-210290dqlpfoamow')
        );

        self::assertArrayHasKey('extracts', $normalizedStatus);
        self::assertArrayHasKey('screen_name', $normalizedStatus);
        self::assertArrayHasKey('statuses', $normalizedStatus);

        self::assertInstanceOf(
            CollectionInterface::class,
            $normalizedStatus['extracts']
        );
        self::assertCount(
            count($allStatuses),
            $normalizedStatus['extracts']
        );

        $taggedStatus = $normalizedStatus['extracts']->first();
        self::assertInstanceOf(
            TaggedStatus::class,
            $normalizedStatus['extracts']->first()
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
    }
}
