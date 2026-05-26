<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Chat\Application\RunChatTurn;
use App\Chat\Application\Stream\SseEvent;
use App\Chat\Infrastructure\ApiPlatform\Resource\ChatTurnResource;
use App\Chat\Infrastructure\Security\BlueskyChatUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<ChatTurnResource, StreamedResponse>
 */
final class RunChatTurnProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RunChatTurn $runChatTurn,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StreamedResponse
    {
        if (trim($data->userMessage) === '') {
            throw new BadRequestHttpException('userMessage is required');
        }

        $user = $this->security->getUser();
        if (!$user instanceof BlueskyChatUser) {
            throw new UnauthorizedHttpException('Bearer', 'Bluesky JWT required');
        }

        $did = $user->did;
        $conversationId = $data->conversationId;
        $userMessage = $data->userMessage;
        $turn = $this->runChatTurn;

        $response = new StreamedResponse(static function () use ($turn, $did, $userMessage, $conversationId): void {
            foreach ($turn($did, $userMessage, $conversationId) as $event) {
                /** @var SseEvent $event */
                echo "event: {$event->type}\n";
                echo 'data: ' . json_encode($event->data, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES) . "\n\n";
                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                flush();
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream; charset=utf-8');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
