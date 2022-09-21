<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Persistence;

use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Publication\Dto\TaggedTweet;
use App\Twitter\Infrastructure\Persistence\PersistenceLayer;
use App\Twitter\Domain\Persistence\PersistenceLayerInterface;
use App\Membership\Infrastructure\Entity\Legacy\Member;
use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** @group tweet_persistence */
class PersistenceTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $memberRepository = $entityManager->getRepository('App\Membership\Infrastructure\Entity\Legacy\Member');
        $members = $memberRepository->findBy(['twitter_username' => 'mariec']);

        array_map(
            function (MemberInterface $member) use ($entityManager) {
                $entityManager->remove($member);
            }, $members
        );

        $entityManager->flush();
    }

    /**
     * @test
     *
     * @throws
     */
    public function it_should_persist_tweets(): void
    {
        // Arrange

        self::$kernel = self::bootKernel();

        /** @var PersistenceLayerInterface $publicationPersistence */
        $publicationPersistence = static::getContainer()->get(PersistenceLayer::class);

        $member = new Member();
        $member->setTwitterScreenName('mariec');
        $member->setName('Marie Curie');
        $member->setAvatar('https://gravatar.com/mariec');
        $member->setEmail('@mariec');

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->persist($member);
        $entityManager->flush();

        // Act

        $normalizedTweets = $publicationPersistence->persistTweetsCollection(
            [
                (object)[
                    'user' => (object)[
                        'screen_name' => 'mariec',
                        'name' => 'Marie Curie',
                        'profile_image_url' => 'https://gravatar.com/mariec',
                    ],
                    'full_text' => 'This is a long tweet, or is it really?',
                    'api_document' => '{}',
                    'id_str' => '42',
                    'created_at' => (
                        new \DateTime(
                            'now',
                            new DateTimeZone('UTC')
                        )
                    )->format(DateTimeInterface::RFC7231)
                ]
            ],
            new AccessToken('12209230996-wpeasq')
        );

        // Assert

        self::assertInstanceOf(
            CollectionInterface::class,
            $normalizedTweets
        );
        self::assertCount(
            1,
            $normalizedTweets->toArray()
        );
        self::assertInstanceOf(
            TaggedTweet::class,
            $normalizedTweets->first()
        );
    }
}
