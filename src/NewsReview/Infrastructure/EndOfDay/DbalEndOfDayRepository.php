<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\EndOfDay;

use App\NewsReview\Domain\EndOfDay\Candidate;
use App\NewsReview\Domain\EndOfDay\ParisDay;
use App\NewsReview\Domain\EndOfDay\Rebuild;
use App\NewsReview\Domain\Snapshot\PublisherList;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

/**
 * Reads the candidates of an end-of-day rebuild and stores its measurements
 * next to the fetched ones, in status_popularity with measured_by = 'rebuild'.
 */
final class DbalEndOfDayRepository
{
    // Bluesky lists whose members are ranked, by publisher list.
    private const LIST_URIS = [
        'fr' => 'at://did:plc:hsvsr3edhbdymtjrg5cifbce/app.bsky.graph.list/3lbcwg7qq3z2h',
        'coop-medias' => 'at://did:plc:hsvsr3edhbdymtjrg5cifbce/app.bsky.graph.list/3mymhj5akx32w',
    ];
    private const UTC = 'Y-m-d H:i:s';

    public function __construct(private readonly Connection $connection)
    {
    }

    /** @return list<Candidate> posts published during the Paris day by the list's members */
    public function candidates(ParisDay $day, PublisherList $list): array
    {
        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
SELECT DISTINCT p.document_id AS uri,
       p.screen_name AS handle,
       (SELECT sp.status_id
          FROM status_popularity sp
         WHERE sp.publication_id = p.document_id
           AND sp.measured_by = 'fetch'
         ORDER BY sp.checked_at ASC
         LIMIT 1) AS status_id
  FROM publication p
  JOIN weaving_user u ON u.usr_twitter_username = p.screen_name
  JOIN publishers_list l ON l.screen_name = u.usr_twitter_id
 WHERE l.name = :list
   AND l.deleted_at IS NULL
   AND p.published_at >= :starts_at
   AND p.published_at < :ends_at
   AND p.document_id LIKE 'at://%'
 ORDER BY p.document_id
SQL, [
            'list' => self::LIST_URIS[$list->value],
            'starts_at' => $day->startsAt()->format(self::UTC),
            'ends_at' => $day->endsAt()->format(self::UTC),
        ]);

        return array_map(
            static fn(array $row): Candidate => new Candidate(
                (string) $row['uri'],
                (string) $row['handle'],
                $row['status_id'] === null ? null : (int) $row['status_id'],
            ),
            $rows,
        );
    }

    /**
     * Replaces the day's rebuilt measurements of these candidates, at the day's
     * cut-offs, with the given rebuild. Fetched measurements are never touched.
     *
     * @param list<Candidate> $candidates
     */
    public function store(Rebuild $rebuild, array $candidates): void
    {
        $uris = array_map(static fn(Candidate $c): string => $c->uri, $candidates);
        $cutOffs = array_map(
            static fn(\DateTimeImmutable $at): string => $at->format(self::UTC),
            array_values($rebuild->day->cutOffs()),
        );

        $this->connection->transactional(function (Connection $connection) use ($rebuild, $uris, $cutOffs): void {
            if ($uris !== []) {
                $connection->executeStatement(
                    "DELETE FROM status_popularity WHERE measured_by = 'rebuild' AND checked_at IN (:cut_offs) AND publication_id IN (:uris)",
                    ['cut_offs' => $cutOffs, 'uris' => $uris],
                    ['cut_offs' => ArrayParameterType::STRING, 'uris' => ArrayParameterType::STRING],
                );
            }
            foreach ($rebuild->measurements() as $measurement) {
                $connection->insert('status_popularity', [
                    'id' => Uuid::v4()->toRfc4122(),
                    'status_id' => $measurement->candidate->statusId,
                    'checked_at' => $measurement->at->format(self::UTC),
                    'total_retweets' => $measurement->reposts,
                    'total_favorites' => $measurement->likes,
                    'publication_id' => $measurement->candidate->uri,
                    'measured_by' => 'rebuild',
                ]);
            }
        });
    }
}
