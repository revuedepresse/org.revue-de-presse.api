<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Repository\Membership;

use App\Membership\Domain\Entity\Member;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Member|null find($id, $lockMode = null, $lockVersion = null)
 * @method Member|null findOneBy(array $criteria, array $orderBy = null)
 * @method Member[]    findAll()
 * @method Member[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MemberRepository extends ServiceEntityRepository implements MemberRepositoryInterface
{
    use LoggerTrait;

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
