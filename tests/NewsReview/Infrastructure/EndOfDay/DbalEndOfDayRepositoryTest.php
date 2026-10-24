<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\EndOfDay;

use App\NewsReview\Domain\EndOfDay\Candidate;
use App\NewsReview\Domain\EndOfDay\ParisDay;
use App\NewsReview\Domain\EndOfDay\Rebuild;
use App\NewsReview\Domain\EndOfDay\RebuiltMeasurement;
use App\NewsReview\Domain\Snapshot\PublisherList;
use App\NewsReview\Infrastructure\EndOfDay\DbalEndOfDayRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

final class DbalEndOfDayRepositoryTest extends TestCase
{
    private const FR = 'at://did:plc:hsvsr3edhbdymtjrg5cifbce/app.bsky.graph.list/3lbcwg7qq3z2h';

    private Connection $db;

    protected function setUp(): void
    {
        $this->db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->db->executeStatement('CREATE TABLE publication (id INTEGER PRIMARY KEY, document_id VARCHAR(255), screen_name VARCHAR(255), published_at DATETIME)');
        $this->db->executeStatement('CREATE TABLE weaving_user (usr_id INTEGER PRIMARY KEY, usr_twitter_username VARCHAR(255), usr_twitter_id VARCHAR(255))');
        $this->db->executeStatement('CREATE TABLE publishers_list (id INTEGER PRIMARY KEY, name VARCHAR(255), screen_name VARCHAR(255), deleted_at DATETIME)');
        $this->db->executeStatement("CREATE TABLE status_popularity (id CHAR(36) NOT NULL, status_id INTEGER, checked_at DATETIME NOT NULL, total_retweets INTEGER NOT NULL, total_favorites INTEGER NOT NULL, publication_id VARCHAR(255), measured_by VARCHAR(16) NOT NULL DEFAULT 'fetch')");

        $this->db->executeStatement("INSERT INTO weaving_user VALUES (1, 'lemonde.fr', 'did:plc:lemonde'), (2, 'other.fr', 'did:plc:other')");
        $this->db->executeStatement("INSERT INTO publishers_list VALUES (1, '" . self::FR . "', 'did:plc:lemonde', NULL), (2, 'at://another/list', 'did:plc:other', NULL)");
        // 23 Oct in Paris is 22 Oct 22:00 to 23 Oct 22:00 UTC.
        $this->db->executeStatement("INSERT INTO publication VALUES
            (1, 'at://lemonde/1', 'lemonde.fr', '2026-10-22 21:59:59'),
            (2, 'at://lemonde/2', 'lemonde.fr', '2026-10-22 22:00:00'),
            (3, 'at://lemonde/3', 'lemonde.fr', '2026-10-23 21:59:59'),
            (4, 'at://other/1', 'other.fr', '2026-10-23 12:00:00')");
        $this->db->executeStatement("INSERT INTO status_popularity (id, status_id, checked_at, total_retweets, total_favorites, publication_id) VALUES
            ('f1', 202, '2026-10-22 23:00:00', 1, 1, 'at://lemonde/2'),
            ('f2', 201, '2026-10-22 22:30:00', 0, 0, 'at://lemonde/2'),
            ('f3', 303, '2026-10-23 22:30:00', 0, 0, 'at://lemonde/3')");
    }

    public function test_candidates_are_the_list_members_posts_of_the_paris_day(): void
    {
        $candidates = (new DbalEndOfDayRepository($this->db))->candidates(ParisDay::of('2026-10-23'), PublisherList::FrenchMedia);

        self::assertSame(
            [['at://lemonde/2', 'lemonde.fr', 201], ['at://lemonde/3', 'lemonde.fr', 303]],
            array_map(static fn(Candidate $c): array => [$c->uri, $c->handle, $c->statusId], $candidates),
        );
    }

    public function test_storing_a_rebuild_twice_replaces_it_and_never_touches_fetched_rows(): void
    {
        $repository = new DbalEndOfDayRepository($this->db);
        $day = ParisDay::of('2026-10-23');
        $candidates = $repository->candidates($day, PublisherList::FrenchMedia);
        $rebuild = static function (int $reposts) use ($day, $candidates): Rebuild {
            $measurements = [];
            foreach ($day->cutOffs() as $label => $at) {
                $measurements[] = new RebuiltMeasurement($candidates[0], $label, $at, $reposts, 2 * $reposts);
            }

            return new Rebuild($day, [], $measurements);
        };

        $repository->store($rebuild(5), $candidates);
        $repository->store($rebuild(7), $candidates);

        self::assertSame(
            [
                [201, '2026-10-23 20:00:00', 7, 14, 'at://lemonde/2'],
                [201, '2026-10-23 22:00:00', 7, 14, 'at://lemonde/2'],
            ],
            array_map(static fn(array $row): array => array_values(array_map(static fn($v) => is_numeric($v) ? (int) $v : $v, $row)), $this->db->fetchAllAssociative("SELECT status_id, checked_at, total_retweets, total_favorites, publication_id FROM status_popularity WHERE measured_by = 'rebuild' ORDER BY checked_at")),
        );
        self::assertSame(3, (int) $this->db->fetchOne("SELECT count(*) FROM status_popularity WHERE measured_by = 'fetch'"));
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $this->db->fetchOne("SELECT id FROM status_popularity WHERE measured_by = 'rebuild' LIMIT 1"));
    }
}
