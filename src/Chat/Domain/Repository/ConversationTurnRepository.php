<?php
declare(strict_types=1);

namespace App\Chat\Domain\Repository;

use App\Chat\Domain\Entity\Conversation;
use App\Chat\Domain\Entity\ConversationTurn;
use Symfony\Component\Uid\Ulid;

interface ConversationTurnRepository
{
    public function append(ConversationTurn $turn): void;

    /**
     * @return list<ConversationTurn> oldest-first, capped at $limit
     */
    public function lastTurns(Conversation $conversation, int $limit): array;

    public function findById(Ulid $id): ?ConversationTurn;
}
