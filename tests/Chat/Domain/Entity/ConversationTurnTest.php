<?php
declare(strict_types=1);

namespace App\Tests\Chat\Domain\Entity;

use App\Chat\Domain\Entity\Conversation;
use App\Chat\Domain\Entity\ConversationTurn;
use PHPUnit\Framework\TestCase;

final class ConversationTurnTest extends TestCase
{
    public function testInvalidRoleThrows(): void
    {
        $conv = new Conversation('did:plc:abc');
        $this->expectException(\InvalidArgumentException::class);
        new ConversationTurn($conv, 'tool', 'oops');
    }

    public function testUserTurnHasMinimalShape(): void
    {
        $conv = new Conversation('did:plc:abc');
        $turn = new ConversationTurn($conv, ConversationTurn::ROLE_USER, 'Bonjour');
        self::assertSame(ConversationTurn::ROLE_USER, $turn->role());
        self::assertSame('Bonjour', $turn->content());
        self::assertNull($turn->citedPublicationIds());
        self::assertNull($turn->provider());
        self::assertNull($turn->promptTokens());
        self::assertNull($turn->completionTokens());
        self::assertFalse($turn->truncated());
        self::assertSame($conv, $turn->conversation());
    }

    public function testAssistantTurnCarriesCitationsProviderAndTokens(): void
    {
        $conv = new Conversation('did:plc:abc');
        $turn = new ConversationTurn(
            $conv,
            ConversationTurn::ROLE_ASSISTANT,
            'Voir [1].',
            citedPublicationIds: ['at://pub-1'],
            provider: 'mistral',
            promptTokens: 320,
            completionTokens: 80,
            truncated: false,
        );
        self::assertSame(['at://pub-1'], $turn->citedPublicationIds());
        self::assertSame('mistral', $turn->provider());
        self::assertSame(320, $turn->promptTokens());
        self::assertSame(80, $turn->completionTokens());
    }

    public function testMarkTruncatedFlipsFlag(): void
    {
        $conv = new Conversation('did:plc:abc');
        $turn = new ConversationTurn($conv, ConversationTurn::ROLE_ASSISTANT, 'partial');
        self::assertFalse($turn->truncated());
        $turn->markTruncated();
        self::assertTrue($turn->truncated());
    }
}
