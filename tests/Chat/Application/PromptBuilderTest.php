<?php
declare(strict_types=1);

namespace App\Tests\Chat\Application;

use App\Chat\Application\PromptBuilder;
use App\Chat\Domain\Retrieval\RetrievedHit;
use PHPUnit\Framework\TestCase;

final class PromptBuilderTest extends TestCase
{
    public function testSystemPromptContainsLanguageConstraint(): void
    {
        $builder = new PromptBuilder();
        $system = $builder->systemPrompt();
        self::assertStringContainsString('Réponds toujours en français', $system);
        self::assertStringContainsString('quelle que soit la langue de la question', $system);
        self::assertStringContainsString('[3]', $system);
    }

    public function testBuildUserMessageWithoutHits(): void
    {
        $builder = new PromptBuilder();
        $msg = $builder->buildUserMessage('Quelle est la une ?', []);
        self::assertSame('Question : Quelle est la une ?', $msg);
    }

    public function testBuildUserMessageWithHits(): void
    {
        $builder = new PromptBuilder();
        $hits = [
            new RetrievedHit(
                publicationId: 'at://x/1',
                screenName: 'lemonde.fr',
                snapshotDate: '2025-03-04',
                url: 'https://bsky.app/profile/lemonde.fr/post/abc',
                text: 'Donald Trump gèle l’aide militaire',
                reposts: 73,
                likes: 169,
                distance: 0.12,
            ),
        ];
        $msg = $builder->buildUserMessage('De quoi parlait-on ?', $hits);
        self::assertStringContainsString('[1] lemonde.fr — 2025-03-04 — 73 reposts', $msg);
        self::assertStringContainsString('Donald Trump gèle l’aide militaire', $msg);
        self::assertStringContainsString('https://bsky.app/profile/lemonde.fr/post/abc', $msg);
        self::assertStringContainsString('Question : De quoi parlait-on ?', $msg);
    }

    public function testLongFrenchDateMatchesExpectedFormat(): void
    {
        $builder = new PromptBuilder();
        $d = new \DateTimeImmutable('2025-03-04', new \DateTimeZone('Europe/Paris'));
        self::assertSame('mardi 4 mars 2025', $builder->longFrenchDate($d));
    }
}
