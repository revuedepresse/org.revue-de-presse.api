<?php
declare(strict_types=1);

namespace App\Tests\Chat\Domain\Query;

use App\Chat\Domain\Query\QueryFilterExtractor;
use PHPUnit\Framework\TestCase;

final class QueryFilterExtractorTest extends TestCase
{
    private const FIXED_NOW = '2026-05-26 10:00:00';

    private QueryFilterExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new QueryFilterExtractor(
            new \DateTimeImmutable(self::FIXED_NOW, new \DateTimeZone('Europe/Paris')),
        );
    }

    public function testExtractsLeMondeOutlet(): void
    {
        $filters = $this->extractor->extract('Que disait Le Monde sur la réforme ?');
        self::assertSame(['lemonde.fr'], $filters->screenNames);
    }

    public function testExtractsMultipleOutlets(): void
    {
        $filters = $this->extractor->extract('Compare Le Monde et Libération sur le sujet');
        $names = $filters->screenNames;
        sort($names);
        self::assertSame(['lemonde.fr', 'liberation.fr'], $names);
    }

    public function testIsAccentInsensitive(): void
    {
        $filters = $this->extractor->extract("L'humanité a-t-il publié ?");
        self::assertSame(['humanite.fr'], $filters->screenNames);
    }

    public function testExtractsYesterday(): void
    {
        $filters = $this->extractor->extract('Qu’est-ce qui était en tête hier ?');
        self::assertNotNull($filters->dateRange->from);
        self::assertSame('2026-05-25', $filters->dateRange->from->format('Y-m-d'));
    }

    public function testExtractsMonthAndYear(): void
    {
        $filters = $this->extractor->extract('Quelle couverture en mars 2025 ?');
        self::assertNotNull($filters->dateRange->from);
        self::assertNotNull($filters->dateRange->to);
        self::assertSame('2025-03-01', $filters->dateRange->from->format('Y-m-d'));
        self::assertSame('2025-03-31', $filters->dateRange->to->format('Y-m-d'));
    }

    public function testExtractsBareMonthDefaultsToCurrentYear(): void
    {
        $filters = $this->extractor->extract('Les unes de mars étaient comment ?');
        self::assertNotNull($filters->dateRange->from);
        self::assertSame('2026-03-01', $filters->dateRange->from->format('Y-m-d'));
    }

    public function testExtractsLastMonth(): void
    {
        $filters = $this->extractor->extract('Que s’est-il passé le mois dernier ?');
        self::assertNotNull($filters->dateRange->from);
        self::assertSame('2026-04-01', $filters->dateRange->from->format('Y-m-d'));
        self::assertNotNull($filters->dateRange->to);
        self::assertSame('2026-04-30', $filters->dateRange->to->format('Y-m-d'));
    }

    public function testExtractsCetteSemaineAsLastSevenDays(): void
    {
        $filters = $this->extractor->extract('Quelles nouvelles cette semaine ?');
        self::assertNotNull($filters->dateRange->from);
        self::assertNotNull($filters->dateRange->to);
        // FIXED_NOW = 2026-05-26 → last 7 days = 2026-05-20..26
        self::assertSame('2026-05-20', $filters->dateRange->from->format('Y-m-d'));
        self::assertSame('2026-05-26', $filters->dateRange->to->format('Y-m-d'));
    }

    public function testExtractsNouvellesDeLaSemaineAsLastSevenDays(): void
    {
        // The phrase that originally surfaced the bug — "nouvelles de la semaine".
        // Must hit the same 7-day window as bare "cette semaine".
        $filters = $this->extractor->extract('nouvelles de la semaine');
        self::assertNotNull($filters->dateRange->from);
        self::assertSame('2026-05-20', $filters->dateRange->from->format('Y-m-d'));
        self::assertSame('2026-05-26', $filters->dateRange->to->format('Y-m-d'));
    }

    public function testExtractsCesDerniersJoursAsLastSevenDays(): void
    {
        $filters = $this->extractor->extract('Quels sujets ont émergé ces derniers jours ?');
        self::assertNotNull($filters->dateRange->from);
        self::assertSame('2026-05-20', $filters->dateRange->from->format('Y-m-d'));
    }

    public function testExtractsRecentAdjectivesAsLastFourteenDays(): void
    {
        foreach (['actualités récentes', 'événements récents', 'une publication récente'] as $phrase) {
            $filters = $this->extractor->extract($phrase);
            self::assertNotNull($filters->dateRange->from, "expected a date filter for: {$phrase}");
            // Last 14 days = 2026-05-13..26
            self::assertSame('2026-05-13', $filters->dateRange->from->format('Y-m-d'),
                "wrong from for: {$phrase}");
        }
    }

    public function testExplicitMonthOverridesGenericRecentPhrase(): void
    {
        // "récents" + "mars 2025" → the specific month wins (rule-based extractor
        // checks specific month/year before bare recency phrases).
        $filters = $this->extractor->extract('événements récents en mars 2025');
        self::assertNotNull($filters->dateRange->from);
        self::assertSame('2025-03-01', $filters->dateRange->from->format('Y-m-d'));
        self::assertSame('2025-03-31', $filters->dateRange->to->format('Y-m-d'));
    }

    public function testDetectsSummaryIntentFromFrenchPhrasings(): void
    {
        $phrases = [
            'Résume la journée',
            'Donne-moi un résumé de cette semaine',
            'Quelle synthèse de mars 2025 ?',
            'Synthétise les sujets du mois',
            'Que s\'est-il passé hier',
            'Quoi de neuf cette semaine',
            'Donne-moi un aperçu',
            'Fais un panorama de la semaine',
        ];
        foreach ($phrases as $p) {
            $filters = $this->extractor->extract($p);
            self::assertTrue($filters->isSummary, "expected summary intent for: {$p}");
        }
    }

    public function testDoesNotDetectSummaryIntentForSpecificQuestions(): void
    {
        $phrases = [
            'Que dit Le Monde sur la réforme ?',
            'Comment Libération a-t-il couvert le sujet ?',
            'Donne-moi les articles d\'aujourd\'hui',
            'Compare Le Monde et Mediapart sur le sujet',
        ];
        foreach ($phrases as $p) {
            $filters = $this->extractor->extract($p);
            self::assertFalse($filters->isSummary, "did NOT expect summary intent for: {$p}");
        }
    }

    public function testUnknownLanguageReturnsEmptyFilters(): void
    {
        $filters = $this->extractor->extract('Какие новости вчера были?');
        self::assertTrue($filters->isEmpty());
    }

    public function testLeMondeDiplomatiqueResolvesToDiplo(): void
    {
        $filters = $this->extractor->extract('Que disait Le Monde diplomatique ?');
        self::assertContains('monde-diplomatique.fr', $filters->screenNames);
    }

    public function testLesEchosResolvesToBskyHandle(): void
    {
        $filters = $this->extractor->extract('Que disaient Les Échos ?');
        self::assertSame(['lesechosfr.bsky.social'], $filters->screenNames);
    }

    public function testCanonicalScreenNamesMatchesBskyManifest(): void
    {
        // Keep CANONICAL_SCREEN_NAMES in sync with `get_authors_feeds` in
        // deployer/.maintaining/org.revue-de-presse.bsky.sh — that script is
        // the source of truth for which outlets the project tracks.
        $expected = [
            'afp.com',
            'bfmtv.com',
            'blast-info.fr',
            'challengesfr.bsky.social',
            'charliehebdo.fr',
            'courrierinter.bsky.social',
            'franceculture.fr',
            'france24.com',
            'humanite.fr',
            'la-croix.com',
            'lavoixdunord.fr',
            'lefigaro.fr',
            'lecanardenchaine.fr',
            'lemonde.fr',
            'afrique.lemonde.fr',
            'lepoint.fr',
            'lesechosfr.bsky.social',
            'lesjours.fr',
            'liberation.fr',
            'mediapart.fr',
            'monde-diplomatique.fr',
            'nouvelobs.com',
            'ouest-france.fr',
            'pixelsfr.bsky.social',
            'rfi.fr',
            'telerama.bsky.social',
        ];
        self::assertSame($expected, \App\Chat\Domain\Query\QueryFilterExtractor::CANONICAL_SCREEN_NAMES);

        $aliasTargets = array_unique(array_values((function (): array {
            $r = new \ReflectionClass(\App\Chat\Domain\Query\QueryFilterExtractor::class);
            $c = $r->getReflectionConstant('OUTLETS');
            self::assertNotFalse($c);

            return $c->getValue();
        })()));
        sort($aliasTargets);
        $canonical = $expected;
        sort($canonical);
        self::assertSame(
            $canonical,
            $aliasTargets,
            'Every alias must resolve to a canonical screen_name, and every canonical screen_name must have at least one alias.',
        );
    }
}
