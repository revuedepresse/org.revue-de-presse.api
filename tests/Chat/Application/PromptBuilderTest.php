<?php
declare(strict_types=1);

namespace App\Tests\Chat\Application;

use App\Chat\Application\PromptBuilder;
use App\Chat\Domain\Retrieval\RetrievalNotice;
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

    public function testBuildUserMessagePrependsFrenchNoteWhenDateFilterWasRelaxed(): void
    {
        $builder = new PromptBuilder();
        $hits = [
            new RetrievedHit(
                publicationId: 'at://x/1',
                screenName: 'telerama.bsky.social',
                snapshotDate: '2026-01-15',
                url: 'https://bsky.app/profile/telerama.bsky.social/post/x',
                text: 'Vieux extrait pertinent',
                reposts: 1,
                likes: 2,
                distance: 0.4,
            ),
        ];

        $msg = $builder->buildUserMessage(
            'Que dit Telerama cette semaine ?',
            $hits,
            RetrievalNotice::DATE_FILTER_RELAXED,
        );

        // The note must precede the extracts block so the model reads
        // the explanation in context. We assert both the explanation and
        // the instruction to mention it in the reply.
        self::assertStringContainsString(
            "Instruction prioritaire : la période demandée ne contient aucune publication",
            $msg,
        );
        self::assertStringContainsString(
            "Tu DOIS commencer ta réponse par une phrase indiquant cette limitation",
            $msg,
        );
        // Note appears before "Extraits", which appears before "Question".
        $notePos = strpos($msg, 'Instruction prioritaire');
        $extractsPos = strpos($msg, 'Extraits');
        $questionPos = strpos($msg, 'Question :');
        self::assertNotFalse($notePos);
        self::assertNotFalse($extractsPos);
        self::assertNotFalse($questionPos);
        self::assertLessThan($extractsPos, $notePos, 'note must come before extracts');
        self::assertLessThan($questionPos, $extractsPos, 'extracts must come before question');
    }

    public function testBuildUserMessageNoteAlsoSurfacesWhenThereAreNoHits(): void
    {
        $builder = new PromptBuilder();

        $msg = $builder->buildUserMessage(
            'Que dit Telerama cette semaine ?',
            [],
            RetrievalNotice::DATE_FILTER_RELAXED,
        );

        self::assertStringContainsString('Instruction prioritaire', $msg);
        self::assertStringContainsString('Question :', $msg);
        self::assertStringNotContainsString('Extraits', $msg);
    }

    public function testLongFrenchDateMatchesExpectedFormat(): void
    {
        $builder = new PromptBuilder();
        $d = new \DateTimeImmutable('2025-03-04', new \DateTimeZone('Europe/Paris'));
        self::assertSame('mardi 4 mars 2025', $builder->longFrenchDate($d));
    }
}
