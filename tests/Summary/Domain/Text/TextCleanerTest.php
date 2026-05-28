<?php
declare(strict_types=1);

namespace App\Tests\Summary\Domain\Text;

use App\Summary\Domain\Text\TextCleaner;
use PHPUnit\Framework\TestCase;

final class TextCleanerTest extends TestCase
{
    private TextCleaner $cleaner;

    protected function setUp(): void
    {
        $this->cleaner = new TextCleaner();
    }

    public function testDecodesLiteralUnicodeEscapes(): void
    {
        // The raw payload below is the literal six-byte sequence `é`
        // that some upstream snapshots leak into the `text` field.
        $raw = 'café';
        self::assertSame('café', $this->cleaner->clean($raw));
    }

    public function testCollapsesNewlinesAndTabsToSingleSpaces(): void
    {
        self::assertSame('a b c', $this->cleaner->clean("a\nb\tc"));
        self::assertSame('a b c', $this->cleaner->clean("a\r\nb\r\nc"));
    }

    public function testStripsControlCharacters(): void
    {
        $raw = "hello\x00\x01\x07world";
        self::assertSame('hello world', $this->cleaner->clean($raw));
    }

    public function testCollapsesNbspAndMultipleSpaces(): void
    {
        $raw = "a\xc2\xa0\xc2\xa0  b";
        self::assertSame('a b', $this->cleaner->clean($raw));
    }

    public function testTrims(): void
    {
        self::assertSame('hello', $this->cleaner->clean("   hello\n\n"));
    }

    public function testIsIdempotent(): void
    {
        $raw = "  café\nhello\tworld\xc2\xa0\xc2\xa0  ";
        $once = $this->cleaner->clean($raw);
        $twice = $this->cleaner->clean($once);
        self::assertSame($once, $twice);
    }

    public function testPreservesFrenchAccents(): void
    {
        $raw = "L'aide militaire à l'Ukraine — gel brutal";
        self::assertSame("L'aide militaire à l'Ukraine — gel brutal", $this->cleaner->clean($raw));
    }

    public function testHandlesEmptyAndWhitespaceOnly(): void
    {
        self::assertSame('', $this->cleaner->clean(''));
        self::assertSame('', $this->cleaner->clean("   \t\n   "));
    }
}
