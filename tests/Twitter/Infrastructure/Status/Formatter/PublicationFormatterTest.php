<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Status\Formatter;

use App\Twitter\Infrastructure\Publication\Formatter\PublicationFormatter;
use App\Twitter\Infrastructure\Operation\Collection\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group formatter
 */
class PublicationFormatterTest extends KernelTestCase
{
    private Collection $publications;

    /**
     * @test
     */
    public function it_formats_publications(): void
    {
        self::$kernel    = self::bootKernel();
        self::$container = self::$kernel->getContainer();

        $publicationFormatter = self::$container->get(PublicationFormatter::class);

        $formattedPublications = $publicationFormatter->format($this->publications);

        self::assertInstanceOf(Collection::class, $formattedPublications);

        $publications = $formattedPublications->toArray();
        self::assertCount(1, $publications);
        self::assertEquals(
            [
                'status_id'       => '1236286713070268416',
                'avatar_url'      => 'https://pbs.twimg.com/profile_images/727173860764844032/0hGX9DZG_normal.jpg',
                'text'            => '@matthewlmcclure @heydonworks https://t.co/znrXtvEbgW',
                'url'             => 'https://twitter.com/adactio/status/1236286713070268416',
                'retweet_count'   => 0,
                'favorite_count'  => 1,
                'username'        => 'adactio',
                'published_at'    => 'Sat Mar 07 13:44:56 +0000 2020',
                'media'           => [],
                'in_conversation' => null,
                'retweet'         => false,
                'original_document' => unserialize(
                    base64_decode(
                        file_get_contents(
                            __DIR__ . '/../../../../Resources/Status.b64'
                        )
                    )
                )
            ],
            $publications[0]
        );
    }

    protected function setUp(): void
    {
        $this->publications = new Collection(
            unserialize(
                strtr(
                    base64_decode(
                        file_get_contents(
                            __DIR__ . '/../../../../Resources/Publications.b64'
                        )
                    ),
                    [
                        '30:"App\Twitter\Entity\Publication' => '49:"App\Twitter\Domain\Publication\Entity\Publication',
                    ]
                )
            )
        );
    }
}