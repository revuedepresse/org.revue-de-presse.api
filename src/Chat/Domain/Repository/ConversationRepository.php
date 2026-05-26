<?php
declare(strict_types=1);

namespace App\Chat\Domain\Repository;

use App\Chat\Domain\Entity\Conversation;
use Symfony\Component\Uid\Ulid;

interface ConversationRepository
{
    public function openOrCreateFor(string $blueskyDid, ?Ulid $existingId = null): Conversation;

    public function save(Conversation $conversation): void;
}
