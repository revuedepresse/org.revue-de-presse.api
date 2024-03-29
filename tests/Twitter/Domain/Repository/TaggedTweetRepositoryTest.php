<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Domain\Repository;

use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Infrastructure\Repository\Status\TaggedTweetRepository;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @package App\Tests\Twitter\Domain\Repository
 * @group tagged_status
 */
class TaggedTweetRepositoryTest extends KernelTestCase
{
    /**
     * @test
     *
     * @throws
     */
    public function it_should_convert_props_to_status(): void
    {
        self::$kernel = self::bootKernel();

        $repository = new TaggedTweetRepository(
            static::getContainer()->get('doctrine.orm.entity_manager'),
            new NullLogger()
        );

        $props = [
            'hash' => md5('_'),
            'screen_name' => 'mariec',
            'name' => 'Marie Curie',
            'status_id' => '1',
            'text' => 'this is a short publication.',
            'user_avatar' => 'https://gravatar.com/user-profile42',
            'api_document' => '{}',
            'created_at' => new \DateTime('now'),
            'identifier' => '12120997-3231083wkeowo'
        ];

        $aggregate = new PublishersList('mariec', 'science');

        $status = $repository->convertPropsToStatus(
            $props,
            $aggregate
        );

        self::assertInstanceOf(Tweet::class, $status);
        self::assertTrue($status->getIndexed());

        self::assertEquals($props['name'], $status->getName());
        self::assertEquals($props['screen_name'], $status->getScreenName());
        self::assertEquals($props['user_avatar'], $status->getUserAvatar());
        self::assertEquals($props['text'], $status->getText());
        self::assertEquals($props['status_id'], $status->getStatusId());
        self::assertEquals($props['api_document'], $status->getApiDocument());
        self::assertEquals($props['identifier'], $status->getIdentifier());
        self::assertEquals($props['created_at'], $status->getCreatedAt());
    }
}
