<?php
declare(strict_types=1);

namespace App\Chat\Domain\Repository;

use App\Chat\Domain\Entity\Conversation;
use Symfony\Component\Uid\Uuid;

interface ConversationRepository
{
    public function openOrCreateFor(string $blueskyDid, ?Uuid $existingId = null): Conversation;

    public function save(Conversation $conversation): void;
}
