<?php
declare(strict_types=1);

namespace App\Chat\Application;

use App\Chat\Domain\Retrieval\RetrievalNotice;
use App\Chat\Domain\Retrieval\RetrievedHit;

/**
 * Assembles the system prompt and user message for the chat completion.
 * The user message embeds the retrieved snippets as a numbered context
 * block so [n] markers in the model's output map deterministically back
 * to publication IDs (see CitationExtractor).
 */
final class PromptBuilder
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Tu es un assistant qui répond aux questions sur la revue de presse française,
fondée sur les publications les plus relayées sur Bluesky chaque jour.

Règles strictes :
- Réponds uniquement à partir des extraits fournis. Si l'information n'y est pas,
  dis "Je n'ai pas l'information dans la revue de presse."
- Cite chaque affirmation par son numéro entre crochets, p.ex. [3].
- Réponds toujours en français, quelle que soit la langue de la question
  (ton neutre, deux à cinq phrases maximum).
- N'invente ni dates, ni chiffres, ni citations.
PROMPT;

    private const FRENCH_WEEKDAYS = [
        'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche',
    ];

    private const FRENCH_MONTHS = [
        1 => 'janvier',
        2 => 'février',
        3 => 'mars',
        4 => 'avril',
        5 => 'mai',
        6 => 'juin',
        7 => 'juillet',
        8 => 'août',
        9 => 'septembre',
        10 => 'octobre',
        11 => 'novembre',
        12 => 'décembre',
    ];

    public function systemPrompt(): string
    {
        return self::SYSTEM_PROMPT;
    }

    /**
     * @param list<RetrievedHit> $hits
     */
    public function buildUserMessage(
        string $cleanedUserMessage,
        array $hits,
        ?RetrievalNotice $notice = null,
    ): string {
        $contextBlock = $this->formatHits($hits);
        $noticeLine = $this->renderNotice($notice);

        if ($contextBlock === '') {
            // Even without extracts, hand the notice to the model so it can
            // explain itself (e.g. "no recent content from that outlet").
            $head = $noticeLine !== '' ? "{$noticeLine}\n\n" : '';

            return "{$head}Question : {$cleanedUserMessage}";
        }

        $head = $noticeLine !== '' ? "{$noticeLine}\n\n" : '';

        return "{$head}Extraits (par ordre de pertinence) :\n{$contextBlock}\n\nQuestion : {$cleanedUserMessage}";
    }

    /**
     * Per-notice French sentence. Empty string when no notice. The sentence
     * is phrased so the assistant naturally repeats / paraphrases it in
     * its answer to the user.
     */
    private function renderNotice(?RetrievalNotice $notice): string
    {
        if ($notice === null) {
            return '';
        }

        return match ($notice) {
            RetrievalNotice::DATE_FILTER_RELAXED =>
                "Note : la période demandée ne contient aucune publication correspondante. "
                . "Les extraits ci-dessous proviennent du même média mais d'autres dates. "
                . "Mentionne brièvement cette limite dans ta réponse.",
        };
    }

    /**
     * Long French date suitable for the embed-input header.
     * Example: "mardi 4 mars 2025"
     */
    public function longFrenchDate(\DateTimeImmutable $date): string
    {
        $dayIndex = ((int) $date->format('N')) - 1;
        $weekday = self::FRENCH_WEEKDAYS[$dayIndex];
        $day = (int) $date->format('j');
        $month = self::FRENCH_MONTHS[(int) $date->format('n')];
        $year = $date->format('Y');

        return "{$weekday} {$day} {$month} {$year}";
    }

    /**
     * @param list<RetrievedHit> $hits
     */
    private function formatHits(array $hits): string
    {
        $lines = [];
        foreach ($hits as $i => $hit) {
            $n = $i + 1;
            $lines[] = "[{$n}] {$hit->screenName} — {$hit->snapshotDate} — {$hit->reposts} reposts";
            $lines[] = "    \"{$hit->text}\"";
            $lines[] = "    {$hit->url}";
        }

        return implode("\n", $lines);
    }
}
