<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Console;

use App\NewsReview\Domain\EndOfDay\EndOfDayRebuilder;
use App\NewsReview\Domain\EndOfDay\ParisDay;
use App\NewsReview\Infrastructure\Console\RebuildEndOfDayCommand;
use App\NewsReview\Infrastructure\EndOfDay\DbalEndOfDayRepository;
use App\Tests\NewsReview\Domain\EndOfDay\FakeInteractions;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class RebuildEndOfDayCommandTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        $this->db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->db->executeStatement('CREATE TABLE publication (id INTEGER PRIMARY KEY, document_id VARCHAR(255), screen_name VARCHAR(255), published_at DATETIME)');
        $this->db->executeStatement('CREATE TABLE weaving_user (usr_id INTEGER PRIMARY KEY, usr_twitter_username VARCHAR(255), usr_twitter_id VARCHAR(255))');
        $this->db->executeStatement('CREATE TABLE publishers_list (id INTEGER PRIMARY KEY, name VARCHAR(255), screen_name VARCHAR(255), deleted_at DATETIME)');
        $this->db->executeStatement("CREATE TABLE status_popularity (id CHAR(36) NOT NULL, status_id INTEGER, checked_at DATETIME NOT NULL, total_retweets INTEGER NOT NULL, total_favorites INTEGER NOT NULL, publication_id VARCHAR(255), measured_by VARCHAR(16) NOT NULL DEFAULT 'fetch')");
        $this->db->executeStatement("INSERT INTO weaving_user VALUES (1, 'reporterre.net', 'did:plc:reporterre')");
        $this->db->executeStatement("INSERT INTO publishers_list VALUES (1, 'at://did:plc:hsvsr3edhbdymtjrg5cifbce/app.bsky.graph.list/3mymhj5akx32w', 'did:plc:reporterre', NULL)");
        $this->db->executeStatement("INSERT INTO publication VALUES (1, 'at://reporterre/1', 'reporterre.net', '2026-10-23 08:00:00')");
        $this->db->executeStatement("INSERT INTO status_popularity (id, status_id, checked_at, total_retweets, total_favorites, publication_id) VALUES ('f1', 9, '2026-10-24 12:00:00', 1, 1, 'at://reporterre/1')");
    }

    private function tester(): CommandTester
    {
        $before = array_values(ParisDay::of('2026-10-23')->cutOffs())[0]->modify('-1 hour');
        $interactions = new FakeInteractions(['at://reporterre/1' => [[$before, $before], [$before]]]);

        return new CommandTester(new RebuildEndOfDayCommand(new EndOfDayRebuilder($interactions), new DbalEndOfDayRepository($this->db)));
    }

    public function test_dry_run_prints_the_top_ten_at_each_cut_off_and_writes_nothing(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['date' => '2026-10-23', '--list' => 'coop-medias', '--dry-run' => true]));
        $display = $tester->getDisplay();
        self::assertStringContainsString('22:00', $display);
        self::assertStringContainsString('midnight', $display);
        self::assertStringContainsString('reporterre.net', $display);
        self::assertSame(0, (int) $this->db->fetchOne("SELECT count(*) FROM status_popularity WHERE measured_by = 'rebuild'"));
    }

    public function test_stores_the_rebuilt_measurements(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['date' => '2026-10-23', '--list' => 'coop-medias']));
        self::assertSame(2, (int) $this->db->fetchOne("SELECT count(*) FROM status_popularity WHERE measured_by = 'rebuild'"));
        self::assertStringContainsString('2 rebuilt measurements stored', $tester->getDisplay());
    }

    public function test_rejects_an_unknown_list_or_date(): void
    {
        self::assertSame(Command::INVALID, $this->tester()->execute(['date' => '2026-10-23', '--list' => 'bogus']));
        self::assertSame(Command::INVALID, $this->tester()->execute(['date' => '23/10/2026']));
    }
}
