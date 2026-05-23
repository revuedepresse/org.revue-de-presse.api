<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Logging;

use App\Newsletter\Infrastructure\Logging\TokenRedactingProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

final class TokenRedactingProcessorTest extends TestCase
{
    private TokenRedactingProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new TokenRedactingProcessor();
    }

    private function makeRecord(array $context): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'newsletter',
            level: Level::Info,
            message: 'test',
            context: $context,
        );
    }

    public function test_redacts_confirm_token_in_request_uri(): void
    {
        $token = str_repeat('A', 43);
        $record = $this->makeRecord(['request_uri' => "/newsletter/confirm/{$token}"]);
        $result = ($this->processor)($record);
        self::assertSame('/newsletter/confirm/<redacted>', $result->context['request_uri']);
    }

    public function test_redacts_unsubscribe_token_in_request_uri(): void
    {
        $token = str_repeat('B', 43);
        $record = $this->makeRecord(['request_uri' => "/newsletter/unsubscribe/{$token}"]);
        $result = ($this->processor)($record);
        self::assertSame('/newsletter/unsubscribe/<redacted>', $result->context['request_uri']);
    }

    public function test_redacts_token_context_key(): void
    {
        $record = $this->makeRecord(['token' => 'super-secret-token-value']);
        $result = ($this->processor)($record);
        self::assertSame('<redacted>', $result->context['token']);
    }

    public function test_leaves_uri_without_token_unchanged(): void
    {
        $record = $this->makeRecord(['request_uri' => '/newsletter/confirm']);
        $result = ($this->processor)($record);
        self::assertSame('/newsletter/confirm', $result->context['request_uri']);
    }

    public function test_passes_through_record_without_sensitive_keys(): void
    {
        $record = $this->makeRecord(['user' => 'alice', 'action' => 'subscribe']);
        $result = ($this->processor)($record);
        self::assertSame('alice', $result->context['user']);
        self::assertSame('subscribe', $result->context['action']);
    }
}
