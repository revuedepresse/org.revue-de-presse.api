<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Chat\Infrastructure\ApiPlatform\State\RunChatTurnProcessor;

#[ApiResource(
    shortName: 'ChatTurn',
    operations: [
        new Post(
            uriTemplate: '/chat/turns',
            processor: RunChatTurnProcessor::class,
            outputFormats: ['text/event-stream' => ['text/event-stream']],
            security: "is_granted('ROLE_BSKY_USER')",
        ),
    ],
)]
final readonly class ChatTurnResource
{
    public function __construct(
        public string $userMessage = '',
        public ?string $conversationId = null,
    ) {
    }
}
