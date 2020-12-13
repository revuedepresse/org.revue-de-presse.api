<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Status\Persistence;

use App\Twitter\Infrastructure\Api\AccessToken\AccessToken;
use App\Twitter\Domain\Publication\TaggedStatus;
use App\Twitter\Infrastructure\Publication\Persistence\PublicationPersistence;
use App\Twitter\Infrastructure\Publication\Persistence\PublicationPersistenceInterface;
use App\Membership\Domain\Entity\Legacy\Member;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Infrastructure\Operation\Collection\CollectionInterface;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group publication
 */
class PublicationPersistenceTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $memberRepository = $entityManager->getRepository('App\Membership\Domain\Entity\Legacy\Member');
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
    public function it_should_persist_status_publications(): void
    {
        // Arrange

        self::$kernel = self::bootKernel();
        self::$container = self::$kernel->getContainer();

        /** @var PublicationPersistenceInterface $publicationPersistence */
        $publicationPersistence = self::$container->get(PublicationPersistence::class);

        $member = new Member();
        $member->setScreenName('mariec');
        $member->setName('Marie Curie');
        $member->setAvatar('https://gravatar.com/mariec');
        $member->setEmail('@mariec');

        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $entityManager->persist($member);
        $entityManager->flush();

        // Act

        $normalizedStatus = $publicationPersistence->persistStatusPublications(
            [
                (object)[
                    'user' => (object)[
                        'screen_name' => 'mariec',
                        'name' => 'Marie Curie',
                        'profile_image_url' => 'https://gravatar.com/mariec',
                    ],
                    'full_text' => 'This is a long status.',
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
            $normalizedStatus
        );
        self::assertCount(
            1,
            $normalizedStatus->toArray()
        );
        self::assertInstanceOf(
            TaggedStatus::class,
            $normalizedStatus->first()
        );
    }
}
