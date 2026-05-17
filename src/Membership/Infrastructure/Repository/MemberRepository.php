<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\Repository;

use App\Membership\Domain\Entity\Member;
use App\Membership\Domain\Repository\MemberRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Psr\Log\LoggerInterface;

/**
 * @method Member|null find($id, $lockMode = null, $lockVersion = null)
 * @method Member|null findOneBy(array $criteria, array $orderBy = null)
 * @method Member[]    findAll()
 * @method Member[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MemberRepository extends ServiceEntityRepository implements MemberRepositoryInterface
{
    protected LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Look up a single enabled member whose `apiKey` column matches the submitted
     * secret. The match happens in the DB rather than in PHP-land — we don't fetch
     * all enabled members and compare in a loop. Portable across sqlite and
     * postgresql.
     */
    public function findEnabledByApiKey(string $submittedSecret): ?Member
    {
        return $this->createQueryBuilder('m')
            ->where('m.enabled = :enabled')
            ->andWhere('m.apiKey = :secret')
            ->setParameter('enabled', true)
            ->setParameter('secret', $submittedSecret)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
