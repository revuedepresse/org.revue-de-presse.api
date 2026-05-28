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

    /**
     * System prompt for summary-intent queries ("résume la semaine", "quoi
     * de neuf"). The cite-everything pressure is relaxed in favour of
     * synthesis: regroup extracts by theme, name the outlets, [N] markers
     * are optional. Keeps Mistral from emitting an enumerated 1-per-line
     * list when the user just wanted an overview.
     */
    private const SYSTEM_PROMPT_SUMMARY = <<<'PROMPT'
Tu es un assistant qui résume la revue de presse française, fondée sur les
publications les plus relayées sur Bluesky chaque jour.

Règles pour ce résumé :
- Donne une synthèse thématique en 3 à 6 paragraphes courts, regroupés par
  sujet (politique, économie, culture, international, etc.).
- Mentionne les médias qui ont relayé chaque sujet, p.ex.
  « Selon Le Monde et Mediapart, … ».
- Tu peux utiliser des marqueurs [N] pour appuyer une affirmation précise,
  mais ce n'est pas obligatoire ; la lisibilité prime.
- Reste fidèle aux extraits : n'invente ni dates, ni chiffres, ni citations.
  Si un sujet apparaît dans un seul extrait, mentionne-le brièvement sans le
  surévaluer.
- Réponds toujours en français, ton neutre et journalistique.
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

    public function systemPrompt(bool $isSummary = false): string
    {
        return $isSummary ? self::SYSTEM_PROMPT_SUMMARY : self::SYSTEM_PROMPT;
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
                "Instruction prioritaire : la période demandée ne contient aucune publication "
                . "du média. Les extraits ci-dessous proviennent du même média mais hors de cette "
                . "période. Tu DOIS commencer ta réponse par une phrase indiquant cette limitation, "
                . "p.ex. « Ce média n'a rien publié sur la période demandée ; voici ses publications "
                . "plus anciennes : ». Ensuite seulement, résume les extraits.",
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
