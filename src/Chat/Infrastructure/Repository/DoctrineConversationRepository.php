<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Repository;

use App\Chat\Domain\Entity\Conversation;
use App\Chat\Domain\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineConversationRepository implements ConversationRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function openOrCreateFor(string $blueskyDid, ?Uuid $existingId = null): Conversation
    {
        if ($existingId !== null) {
            $existing = $this->em->getRepository(Conversation::class)->find($existingId);
            if ($existing instanceof Conversation && $existing->blueskyDid() === $blueskyDid) {
                return $existing;
            }
        }

        $conversation = new Conversation($blueskyDid);
        $this->em->persist($conversation);
        $this->em->flush();

        return $conversation;
    }

    public function save(Conversation $conversation): void
    {
        $this->em->persist($conversation);
        $this->em->flush();
    }
}
