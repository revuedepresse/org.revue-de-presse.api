<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Repository\Status;

use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Http\Entity\ArchivedTweet;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Domain\Publication\Repository\TaggedTweetRepositoryInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Publication\Dto\TaggedTweet;
use App\Twitter\Infrastructure\DependencyInjection\Status\TweetRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;

class TaggedTweetRepository extends ServiceEntityRepository implements TaggedTweetRepositoryInterface
{
    use TweetRepositoryTrait;

    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager    = $entityManager;
        $this->logger           = $logger;
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function convertPropsToStatus(
        array $properties,
        ?PublishersList $aggregate
    ): TweetInterface {
        $taggedTweet = TaggedTweet::fromLegacyProps($properties);

        if ($this->statusHavingHashExists($taggedTweet->hash())) {
            return $this->statusRepository->reviseDocument($taggedTweet);
        }

        return $taggedTweet->toStatus(
            $this->entityManager,
            $this->logger,
            $aggregate
        );
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function archivedStatusHavingHashExists(string $hash): bool
    {
        $queryBuilder = $this->entityManager
            ->getRepository(ArchivedTweet::class)
            ->createQueryBuilder('s');
        $queryBuilder->select('count(s.id) as count_')
                     ->andWhere('s.hash = :hash');

        $queryBuilder->setParameter('hash', $hash);
        $count = (int) $queryBuilder->getQuery()->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function statusHavingHashExists(string $hash): bool
    {
        if ($this->archivedStatusHavingHashExists($hash)) {
            return true;
        }

        $queryBuilder = $this->entityManager
            ->getRepository(Tweet::class)
            ->createQueryBuilder('s');
        $queryBuilder->select('count(s.id) as count_')
                     ->andWhere('s.hash = :hash');

        $queryBuilder->setParameter('hash', $hash);
        $count = (int) $queryBuilder->getQuery()->getSingleScalarResult();

        return $count > 0;
    }
}
