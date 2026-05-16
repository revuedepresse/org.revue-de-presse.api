<?php
declare(strict_types=1);

namespace App\Tests\Trends\Infrastructure\Controller;

use App\Trends\Infrastructure\Performance\PerformanceMetrics;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Performance harness for GET /api/twitter/highlights.
 *
 * Runs only when BENCHMARK_HOST and API_AUTH_TOKEN are set in the test env
 * (.env.test or .env.test.local — .env.local is intentionally NOT loaded
 * by Symfony Dotenv in test mode).
 *
 * Invoke with:
 *
 *     bin/phpunit -c ./phpunit.xml.dist --group performance
 *
 * @group performance
 */
class HighlightsPerformanceTest extends TestCase
{
    public function test_measures_highlights_endpoint_latency_distribution(): void
    {
        $host  = (string) ($_ENV['BENCHMARK_HOST']   ?? '');
        $token = (string) ($_ENV['API_AUTH_TOKEN']   ?? '');

        if ($host === '') {
            self::markTestSkipped('BENCHMARK_HOST is empty in env (.env.test).');
        }
        if ($token === '') {
            self::markTestSkipped('API_AUTH_TOKEN is empty in env (.env.test).');
        }

        $base = str_contains($host, '://') ? $host : 'https://' . $host;
        $url  = rtrim($base, '/') . '/api/twitter/highlights';

        $iterations = (int) ($_ENV['BENCH_ITERATIONS'] ?? 50);
        $warmup     = (int) ($_ENV['BENCH_WARMUP']     ?? 3);

        $client = HttpClient::create([
            'timeout' => 30.0,
            'headers' => [
                'x-auth-token' => $token,
                'x-benchmark'  => '1',
            ],
        ]);

        $requestOptions = [
            'query' => [
                'startDate'       => '2024-01-01 00:00:00',
                'endDate'         => '2024-01-01 23:59:59',
                'includeRetweets' => '0',
            ],
        ];

        // Warmup — not counted.
        for ($i = 0; $i < $warmup; $i++) {
            try {
                $client->request('GET', $url, $requestOptions)->getContent(false);
            } catch (TransportExceptionInterface) {
                // ignore during warmup
            }
        }

        $samples = [];
        $errors  = 0;
        $wallStart = hrtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $start = hrtime(true);
            try {
                $response = $client->request('GET', $url, $requestOptions);
                // Force the body read so the timing reflects end-to-end work.
                $response->getContent(false);
                $status = $response->getStatusCode();
            } catch (TransportExceptionInterface) {
                $errors++;
                continue;
            }
            $elapsedMs = (hrtime(true) - $start) / 1_000_000.0;

            if ($status !== 200) {
                $errors++;
                continue;
            }
            $samples[] = $elapsedMs;
        }

        $wallSeconds = (hrtime(true) - $wallStart) / 1_000_000_000.0;

        self::assertSame(
            0,
            $errors,
            sprintf('%d of %d timed requests failed against %s', $errors, $iterations, $url)
        );
        self::assertNotEmpty($samples, 'No timed samples were collected');

        $metrics = new PerformanceMetrics($samples, $errors);

        $output = new StreamOutput(STDERR);
        $table = new Table($output);
        $table->setHeaders(['Metric', 'Value']);
        $table->setRows([
            ['URL',              $url],
            ['Iterations',       (string) $metrics->count()],
            ['Warmup',           (string) $warmup],
            ['Errors',           (string) $metrics->errors()],
            ['Min (ms)',         number_format($metrics->min(), 2)],
            ['p50 (ms)',         number_format($metrics->p50(), 2)],
            ['p95 (ms)',         number_format($metrics->p95(), 2)],
            ['p99 (ms)',         number_format($metrics->p99(), 2)],
            ['Max (ms)',         number_format($metrics->max(), 2)],
            ['Mean (ms)',        number_format($metrics->mean(), 2)],
            ['Throughput (rps)', number_format($metrics->throughput($wallSeconds), 2)],
        ]);
        $table->render();
    }
}
