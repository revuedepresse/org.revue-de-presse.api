<?php
declare(strict_types=1);

namespace App\Tests\Chat\Application\Stream;

use App\Chat\Application\Stream\SseEvent;
use PHPUnit\Framework\TestCase;

final class SseEventTest extends TestCase
{
    public function testTokenFactoryCarriesDelta(): void
    {
        $event = SseEvent::token('Bonjour');
        self::assertSame('token', $event->type);
        self::assertSame(['delta' => 'Bonjour'], $event->data);
    }

    public function testDoneFactoryCarriesConversationAndCitations(): void
    {
        $event = SseEvent::done('01j-conv', [
            ['n' => 1, 'publicationId' => 'p1'],
        ]);
        self::assertSame('done', $event->type);
        self::assertSame('01j-conv', $event->data['conversationId']);
        self::assertSame([['n' => 1, 'publicationId' => 'p1']], $event->data['citations']);
    }

    public function testErrorFactoryShapesCode(): void
    {
        $event = SseEvent::error('rate_limited_user');
        self::assertSame('error', $event->type);
        self::assertSame(['code' => 'rate_limited_user'], $event->data);
    }

    public function testErrorFactoryAttachesDetailsWhenProvided(): void
    {
        $event = SseEvent::error('rate_limited_user', ['retryAfter' => 60]);
        self::assertSame('error', $event->type);
        self::assertSame(
            ['code' => 'rate_limited_user', 'details' => ['retryAfter' => 60]],
            $event->data,
        );
    }
}
