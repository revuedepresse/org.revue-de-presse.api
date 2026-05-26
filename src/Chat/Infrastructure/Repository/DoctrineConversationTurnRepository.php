<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Repository;

use App\Chat\Domain\Entity\Conversation;
use App\Chat\Domain\Entity\ConversationTurn;
use App\Chat\Domain\Repository\ConversationTurnRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineConversationTurnRepository implements ConversationTurnRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function append(ConversationTurn $turn): void
    {
        $turn->conversation()->touch($turn->createdAt());
        $this->em->persist($turn);
        $this->em->persist($turn->conversation());
        $this->em->flush();
    }

    /**
     * @return list<ConversationTurn>
     */
    public function lastTurns(Conversation $conversation, int $limit): array
    {
        /** @var list<ConversationTurn> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('t')
            ->from(ConversationTurn::class, 't')
            ->where('t.conversation = :c')
            ->orderBy('t.createdAt', 'DESC')
            ->setParameter('c', $conversation)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_reverse($rows);
    }

    public function findById(Uuid $id): ?ConversationTurn
    {
        return $this->em->getRepository(ConversationTurn::class)->find($id);
    }
}
