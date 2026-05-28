<?php
declare(strict_types=1);

namespace App\Summary\Application;

use App\Summary\Application\Port\ChatStreamer;
use App\Summary\Domain\Text\TextCleaner;
use App\NewsReview\Domain\Model\HighlightDto;
use App\NewsReview\Domain\Snapshot\Filter\HighlightNormalizer;
use App\NewsReview\Domain\Snapshot\SnapshotReader;
use App\Summary\Domain\DailySummary;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Generates one day's thematic synthesis from the top-10 Bluesky
 * publications stored in src/Bluesky/Resources/{date}.json. Returns null
 * when the snapshot is missing or empty so callers can skip gracefully
 * (the corpus has gaps).
 *
 * Inputs come from the snapshot directly, NOT pgvector retrieval:
 * the day's "top-10 most relayed" is already curated upstream, and we
 * want the same 10 every time (deterministic), not a re-ranked subset.
 *
 * Token budget is the same SUMMARY_MAX_TOKENS the live chat /discuter
 * summary mode uses (~700) — multi-paragraph French syntheses don't fit
 * in the platform's default cap.
 */
final class DailySummaryGenerator implements DailySummaryGeneratorInterface
{
    // 700 was too tight: a 10-publication day with 5-6 thematic sections
    // would truncate mid-bullet. 1200 leaves enough headroom for the
    // structured French syntheses Mistral produces without bloating
    // generation time.
    private const MAX_TOKENS = 1200;

    private const SYSTEM_PROMPT = <<<'PROMPT'
Tu es un rédacteur en chef qui résume la revue de presse française du jour.

Tu reçois les 10 publications Bluesky les plus relayées d'une date donnée.
Produis une synthèse en français destinée aux lecteurs qui n'ont pas suivi
l'actualité du jour.

Règles :
- 3 à 6 paragraphes courts en flow continu (l'un après l'autre). Tu peux
  regrouper deux publications proches dans le même paragraphe mais
  n'INVENTE PAS de catégorie thématique : pas de titres de section, pas
  de regroupements arbitraires comme « Économie » ou « Culture » — ces
  étiquettes se révèlent souvent imprécises (un papier sur la canicule
  n'est pas de l'économie).
- Cite chaque média par son handle Bluesky EXACT, en MINUSCULES, copié
  tel quel depuis la liste des publications ci-dessous (afp.com,
  lemonde.fr, mediapart.fr, franceculture.fr, liberation.fr, etc.). Le
  handle est un identifiant technique : tu ne dois ni le réécrire en
  capitales, ni en changer le suffixe. Exemples interdits qui doivent
  rester corrects : « AFP », « AFP.fr », « Mediapart.fr », « Le Monde »,
  « France Culture », « Agence France-Presse », « Afrique France Presse ».
  À la place, écris littéralement : « selon afp.com », « selon lemonde.fr »,
  « selon mediapart.fr », « selon franceculture.fr ». N'utilise ni
  guillemets ni italiques ni parenthèses contenant une expansion autour
  des handles.
- Si une publication isolée n'a pas de lien thématique avec les autres,
  mentionne-la brièvement en fin de synthèse sans la sur-représenter.
- INTERDICTION ABSOLUE d'inventer une publication, un média, une affaire,
  une personne, une date, un chiffre ou une citation qui ne figure PAS
  dans la liste numérotée [1]…[10] ci-dessous. Si une information n'est
  pas dans les extraits fournis, tu ne dois pas la mentionner — même si
  tu crois la connaître par ailleurs.
- Le résumé doit faire référence UNIQUEMENT aux médias listés dans la
  source. N'ajoute pas de paragraphe de conclusion du type « En dehors
  de ces publications, il a également été signalé… », « Par ailleurs,
  on rapporte que… », « Pour conclure, … » qui introduit du contenu
  hors des extraits. Le dernier paragraphe doit porter sur l'une des
  publications fournies, sans autre contenu.
- Ton neutre, factuel.
- Format de sortie : markdown valide, paragraphes séparés par une ligne
  vide. PAS de titres (#, ##, ###, ####). Listes à puces possibles avec
  "- " si vraiment utile. N'inclus pas de front-matter, ne répète pas
  la date dans le titre — le titre de la page est ajouté ailleurs.
PROMPT;

    private const FRENCH_WEEKDAYS = [
        'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche',
    ];

    private const FRENCH_MONTHS = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly SnapshotReader $snapshotReader,
        private readonly HighlightNormalizer $normalizer,
        private readonly TextCleaner $textCleaner,
        private readonly ChatStreamer $streamer,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function generate(string $date): ?DailySummary
    {
        $rows = $this->snapshotReader->read($date);
        $highlights = array_values(array_filter(
            array_map(
                fn (mixed $row): ?HighlightDto => is_array($row) ? $this->normalizer->toDto($row) : null,
                $rows,
            ),
            static fn (?HighlightDto $h): bool => $h !== null,
        ));
        if ($highlights === []) {
            return null;
        }

        $messages = [
            ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
            ['role' => 'user',   'content' => $this->buildUserMessage($date, $highlights)],
        ];

        $markdown = '';
        foreach ($this->streamer->stream($messages, ['max_tokens' => self::MAX_TOKENS]) as $delta) {
            $markdown .= $delta;
        }
        $body = trim($markdown) . "\n";

        // Generation-time observability — publication count is transient data
        // that doesn't belong on the DTO (the read-side has no way to recover
        // it from the markdown body). Log here so backfill runs can be audited
        // after the fact.
        $this->logger->info('chat.summary.generated', [
            'date' => $date,
            'publication_count' => count($highlights),
            'markdown_bytes' => strlen($body),
        ]);

        return new DailySummary(date: $date, markdown: $body);
    }

    /**
     * @param list<HighlightDto> $highlights
     */
    private function buildUserMessage(string $date, array $highlights): string
    {
        $head = "Date : " . $this->longFrenchDate($date) . "\n\nPublications du jour :\n";
        $lines = [];
        foreach ($highlights as $i => $h) {
            $n = $i + 1;
            $cleaned = $this->textCleaner->clean($h->text);
            $lines[] = "[{$n}] {$h->screenName} — {$h->reposts} reposts, {$h->likes} likes";
            $lines[] = "    \"{$cleaned}\"";
            if ($h->url !== '') {
                $lines[] = '    ' . $h->url;
            }
        }

        return $head . "\n" . implode("\n", $lines);
    }

    private function longFrenchDate(string $date): string
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $date)
            ?: new \DateTimeImmutable('1970-01-01');
        $weekday = self::FRENCH_WEEKDAYS[((int) $d->format('N')) - 1];
        $month = self::FRENCH_MONTHS[(int) $d->format('n')];

        return "{$weekday} {$d->format('j')} {$month} {$d->format('Y')}";
    }
}
