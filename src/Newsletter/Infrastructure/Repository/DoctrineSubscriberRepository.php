<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Repository;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Repository\SubscriberRepository;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSubscriberRepository implements SubscriberRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {}

    public function save(Subscriber $subscriber): void
    {
        $this->em->persist($subscriber);
        $this->em->flush();
    }

    public function findByEmailHash(string $emailHash): ?Subscriber
    {
        return $this->em->getRepository(Subscriber::class)
            ->findOneBy(['emailHash' => $emailHash]);
    }

    public function findByConfirmToken(OpaqueToken $token): ?Subscriber
    {
        return $this->em->getRepository(Subscriber::class)
            ->findOneBy(['confirmToken' => $token->value()]);
    }

    public function findByUnsubToken(OpaqueToken $token): ?Subscriber
    {
        return $this->em->getRepository(Subscriber::class)
            ->findOneBy(['unsubToken' => $token->value()]);
    }

    public function iterateActive(int $batchSize = 200): iterable
    {
        return $this->iterateByStatus(SubscriberStatus::Active->value, $batchSize);
    }

    public function iterateByStatus(string $status, int $batchSize = 200): iterable
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from(Subscriber::class, 's')
            ->where('s.status = :status')
            ->orderBy('s.id', 'ASC')
            ->setParameter('status', $status);

        $offset = 0;
        do {
            $rows = $qb->setFirstResult($offset)->setMaxResults($batchSize)->getQuery()->getResult();
            foreach ($rows as $row) {
                yield $row;
            }
            $offset += $batchSize;
            $this->em->clear();
        } while (count($rows) === $batchSize);
    }
}
