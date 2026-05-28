<?php
declare(strict_types=1);

namespace App\Chat\Domain\Query;

/**
 * Rule-based French phrasebook: pulls cheap structured filters
 * (date range, outlet screen-names) out of a user message before
 * vector search. No LLM call. Failure mode is benign: unrecognised
 * inputs degrade to a broader pure-vector search.
 */
final class QueryFilterExtractor
{
    /**
     * Outlet aliases folded (lowercase + transliterated to ASCII) → Bluesky
     * screen_name. The screen_name list mirrors `get_authors_feeds` in
     * `deployer/.maintaining/org.revue-de-presse.bsky.sh` — keep them in sync.
     *
     * Aliases are matched with `str_contains` against the folded user message.
     * Longer, more-specific aliases come first so "le monde diplomatique" hits
     * the diplo outlet before (also) hitting the generic Le Monde entry.
     *
     * @var array<string, string>
     */
    private const OUTLETS = [
        // most specific first
        'le monde diplomatique' => 'monde-diplomatique.fr',
        'monde diplomatique' => 'monde-diplomatique.fr',
        'monde diplo' => 'monde-diplomatique.fr',
        'le monde afrique' => 'afrique.lemonde.fr',
        'lemonde afrique' => 'afrique.lemonde.fr',
        'courrier international' => 'courrierinter.bsky.social',
        'courrier inter' => 'courrierinter.bsky.social',
        'le canard enchaine' => 'lecanardenchaine.fr',
        'canard enchaine' => 'lecanardenchaine.fr',
        'le canard' => 'lecanardenchaine.fr',
        'la voix du nord' => 'lavoixdunord.fr',
        'voix du nord' => 'lavoixdunord.fr',
        'france culture' => 'franceculture.fr',
        'france 24' => 'france24.com',
        'france24' => 'france24.com',
        'le nouvel obs' => 'nouvelobs.com',
        'nouvel obs' => 'nouvelobs.com',
        'ouest france' => 'ouest-france.fr',
        'ouest-france' => 'ouest-france.fr',
        'charlie hebdo' => 'charliehebdo.fr',
        'charliehebdo' => 'charliehebdo.fr',
        'les echos' => 'lesechosfr.bsky.social',
        'lesechos' => 'lesechosfr.bsky.social',
        'le figaro' => 'lefigaro.fr',
        'lefigaro' => 'lefigaro.fr',
        'le monde' => 'lemonde.fr',
        'lemonde' => 'lemonde.fr',
        'le point' => 'lepoint.fr',
        'lepoint' => 'lepoint.fr',
        'la croix' => 'la-croix.com',
        'lacroix' => 'la-croix.com',
        "l'humanite" => 'humanite.fr',
        'humanite' => 'humanite.fr',
        'les jours' => 'lesjours.fr',
        'lesjours' => 'lesjours.fr',
        'mediapart' => 'mediapart.fr',
        'liberation' => 'liberation.fr',
        'libe' => 'liberation.fr',
        'telerama' => 'telerama.bsky.social',
        'challenges' => 'challengesfr.bsky.social',
        'pixels' => 'pixelsfr.bsky.social',
        'bfmtv' => 'bfmtv.com',
        'bfm tv' => 'bfmtv.com',
        'blast' => 'blast-info.fr',
        'rfi' => 'rfi.fr',
        'afp' => 'afp.com',
        'l\'obs' => 'nouvelobs.com',
        'lobs' => 'nouvelobs.com',
    ];

    /** @var list<string> the 26 canonical screen_names (kept in sync with bsky.sh) */
    public const CANONICAL_SCREEN_NAMES = [
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

    /** @var array<string, int> month name (folded) → 1..12 */
    private const MONTHS = [
        'janvier' => 1,
        'fevrier' => 2,
        'mars' => 3,
        'avril' => 4,
        'mai' => 5,
        'juin' => 6,
        'juillet' => 7,
        'aout' => 8,
        'septembre' => 9,
        'octobre' => 10,
        'novembre' => 11,
        'decembre' => 12,
    ];

    public function __construct(private readonly ?\DateTimeImmutable $now = null)
    {
    }

    public function extract(string $userMessage): QueryFilters
    {
        $folded = $this->fold($userMessage);

        return new QueryFilters(
            dateRange: $this->extractDateRange($folded),
            screenNames: $this->extractOutlets($folded),
            isSummary: $this->detectSummaryIntent($folded),
        );
    }

    /**
     * Detect a "give me an overview" intent vs a specific question. Folded
     * means lowercase + ASCII-transliterated, so we match against
     * "resume"/"resumee" (decoding of "résumé") etc.
     *
     * Substring matches are deliberately broad: a query like "Que s'est-il
     * passé hier" or "Quoi de neuf cette semaine" should fire. False
     * positives that look like a specific question keep the system prompt's
     * cite-everything rule, which is fine — Mistral handles both styles.
     */
    private function detectSummaryIntent(string $folded): bool
    {
        $triggers = [
            'resume',          // matches "résume", "résumé", "résumer"
            'synthese',        // "synthèse"
            'syntheti',        // "synthétise(r)"
            'apercu',          // "aperçu"
            'panorama',
            'que s\'est-il passe',  // "que s'est-il passé"
            'que s est il passe',
            'que se passe-t-il',
            'que se passe t il',
            'quoi de neuf',
        ];
        foreach ($triggers as $needle) {
            if (str_contains($folded, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function fold(string $value): string
    {
        $lower = mb_strtolower($value, 'UTF-8');
        if (!class_exists(\Transliterator::class)) {
            return $lower;
        }
        $tr = \Transliterator::create('Any-Latin; Latin-ASCII; Lower();');

        return $tr === null ? $lower : ((string) $tr->transliterate($lower));
    }

    private function extractDateRange(string $folded): DateRange
    {
        $now = $this->now ?? new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

        if (str_contains($folded, 'hier')) {
            $yesterday = $now->modify('-1 day')->setTime(0, 0, 0);

            return new DateRange($yesterday, $yesterday);
        }

        if (str_contains($folded, "aujourd'hui") || str_contains($folded, 'aujourdhui')) {
            $today = $now->setTime(0, 0, 0);

            return new DateRange($today, $today);
        }

        if (str_contains($folded, 'mois dernier') || str_contains($folded, 'le mois passe')) {
            $first = $now->modify('first day of previous month')->setTime(0, 0, 0);
            $last = $now->modify('last day of previous month')->setTime(23, 59, 59);

            return new DateRange($first, $last);
        }

        if (str_contains($folded, 'semaine derniere') || str_contains($folded, 'la semaine passee')) {
            $start = $now->modify('monday last week')->setTime(0, 0, 0);
            $end = $start->modify('+6 days')->setTime(23, 59, 59);

            return new DateRange($start, $end);
        }

        // "<month> <year>" → that whole month. Specific dates always beat the
        // looser "recents/cette semaine" heuristics below (a user asking
        // "événements récents en mars 2025" wants March 2025, not last week).
        if (preg_match('/\b(' . implode('|', array_keys(self::MONTHS)) . ')\s+(\d{4})\b/u', $folded, $m) === 1) {
            $month = self::MONTHS[$m[1]];
            $year = (int) $m[2];
            $first = (new \DateTimeImmutable())->setDate($year, $month, 1)->setTime(0, 0, 0);
            $last = $first->modify('last day of this month')->setTime(23, 59, 59);

            return new DateRange($first, $last);
        }

        // bare "<month>" without year → that month in the current year
        if (preg_match('/\b(' . implode('|', array_keys(self::MONTHS)) . ')\b/u', $folded, $m) === 1) {
            $month = self::MONTHS[$m[1]];
            $year = (int) $now->format('Y');
            $first = (new \DateTimeImmutable())->setDate($year, $month, 1)->setTime(0, 0, 0);
            $last = $first->modify('last day of this month')->setTime(23, 59, 59);

            return new DateRange($first, $last);
        }

        // Recency phrasings — these only fire after explicit date phrases
        // and month names have been ruled out. Each maps to a fixed
        // backward-looking window ending today.
        //
        // 14-day window: things "récent(e)(s)" can plausibly span half a month.
        // The substring 'recent' matches all of recent/recente/recentes
        // (after fold + ASCII transliteration). Window is today + 13 prior
        // days (inclusive both ends) — matches what a user means by "the
        // last two weeks".
        if (str_contains($folded, 'recent')) {
            $start = $now->modify('-13 days')->setTime(0, 0, 0);

            return new DateRange($start, $now->setTime(23, 59, 59));
        }

        // 3-day window: tightest informal recency. Today + 2 prior days.
        if (str_contains($folded, 'ces jours-ci') || str_contains($folded, 'ces jours ci')) {
            $start = $now->modify('-2 days')->setTime(0, 0, 0);

            return new DateRange($start, $now->setTime(23, 59, 59));
        }

        // 7-day window covering the common "week" phrasings. Matches:
        //   "cette semaine", "la semaine" (e.g. "nouvelles de la semaine"),
        //   "ces derniers jours", "ces derniers temps", "il y a une semaine",
        //   bare "nouvelles" (a French speaker asking about "nouvelles"
        //   without further qualification means current news, not the
        //   whole archive).
        $sevenDayTriggers = [
            'cette semaine',
            'la semaine',
            'ces derniers jours',
            'ces derniers temps',
            'il y a une semaine',
            'nouvelles',
        ];
        foreach ($sevenDayTriggers as $trigger) {
            if (str_contains($folded, $trigger)) {
                // Today + 6 prior days = 7-day inclusive window.
                $start = $now->modify('-6 days')->setTime(0, 0, 0);

                return new DateRange($start, $now->setTime(23, 59, 59));
            }
        }

        return new DateRange();
    }

    /**
     * @return list<string>
     */
    private function extractOutlets(string $folded): array
    {
        $hits = [];
        foreach (self::OUTLETS as $alias => $screen) {
            if (str_contains($folded, $alias)) {
                $hits[$screen] = true;
            }
        }

        return array_keys($hits);
    }
}
