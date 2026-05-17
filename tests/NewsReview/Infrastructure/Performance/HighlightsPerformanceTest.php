<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Performance;

use App\NewsReview\Infrastructure\Performance\PerformanceMetrics;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
class HighlightsPerformanceTest extends KernelTestCase
{
    public function test_measures_highlights_endpoint_latency_distribution(): void
    {
        $host   = (string) ($_ENV['BENCHMARK_HOST']    ?? '');
        $secret = (string) ($_ENV['API_CLIENT_SECRET'] ?? $_ENV['API_AUTH_TOKEN'] ?? '');

        if ($host === '') {
            self::markTestSkipped('BENCHMARK_HOST is empty in env (.env.test).');
        }
        if ($secret === '') {
            self::markTestSkipped('API_CLIENT_SECRET is empty in env (.env.test).');
        }

        self::bootKernel();
        /** @var LoggerInterface $logger */
        $logger = static::getContainer()->get('monolog.logger.benchmark');

        $base = str_contains($host, '://') ? $host : 'https://' . $host;
        $url  = rtrim($base, '/') . '/api/highlights';

        // Mint a short-lived Bearer using the long-lived client secret. The
        // harness loop then sends Authorization: Bearer for every request.
        $tokenMint = HttpClient::create()->request(
            'POST',
            rtrim($base, '/') . '/api/token',
            ['headers' => ['Authorization' => 'Basic ' . base64_encode(':' . $secret), 'Accept' => 'application/json']],
        );
        $tokenBody = $tokenMint->toArray(false);
        $bearer = $tokenBody['access_token'] ?? null;
        if ($bearer === null) {
            self::markTestSkipped(sprintf(
                'Could not mint a bearer against %s (status=%d, body=%s)',
                $base,
                $tokenMint->getStatusCode(),
                json_encode($tokenBody),
            ));
        }

        $iterations   = (int) (getenv('BENCH_ITERATIONS')    ?: $_SERVER['BENCH_ITERATIONS']    ?? $_ENV['BENCH_ITERATIONS']    ?? 50);
        $warmup       = (int) (getenv('BENCH_WARMUP')        ?: $_SERVER['BENCH_WARMUP']        ?? $_ENV['BENCH_WARMUP']        ?? 3);
        $concurrency  = (int) (getenv('BENCH_CONCURRENCY')   ?: $_SERVER['BENCH_CONCURRENCY']   ?? $_ENV['BENCH_CONCURRENCY']   ?? 1);
        $timeoutSec   = (float) (getenv('BENCH_TIMEOUT')     ?: $_SERVER['BENCH_TIMEOUT']       ?? $_ENV['BENCH_TIMEOUT']       ?? 30.0);
        // BENCH_BYPASS_CACHE: "1" (default) sends x-benchmark and the controller
        // skips Redis. "0" leaves the header off so the cache serves hits after
        // the first miss — measures the cache-warm path instead of DB latency.
        $bypassCache  = ((string) (getenv('BENCH_BYPASS_CACHE') ?: $_SERVER['BENCH_BYPASS_CACHE'] ?? $_ENV['BENCH_BYPASS_CACHE'] ?? '1')) !== '0';
        if ($concurrency < 1) {
            $concurrency = 1;
        }

        $runId = bin2hex(random_bytes(4));
        $logger->info('benchmark.start', [
            'run_id'       => $runId,
            'url'          => $url,
            'iterations'   => $iterations,
            'concurrency'  => $concurrency,
            'warmup'       => $warmup,
            'bypass_cache' => $bypassCache,
        ]);

        $headers = ['Authorization' => 'Bearer ' . $bearer];
        if ($bypassCache) {
            $headers['x-benchmark'] = '1';
        }

        $client = HttpClient::create(
            [
                'timeout' => $timeoutSec,
                'headers' => $headers,
            ],
            // Bump the per-host connection cap so concurrency > 6 is actually
            // honored. Symfony's curl transport defaults this to 6.
            maxHostConnections: max(6, $concurrency)
        );

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

        $samples          = [];
        $errors           = 0;
        $statusHistogram  = [];
        $cacheHistogram   = ['hit' => 0, 'miss' => 0, 'bypass' => 0, 'error' => 0, 'unknown' => 0, 'absent' => 0];
        $firstErrorIter   = null;
        $firstErrorStatus = null;
        $wallStart        = hrtime(true);

        $completed = 0;
        while ($completed < $iterations) {
            $batchSize = min($concurrency, $iterations - $completed);

            // Dispatch a batch of lazy responses. Symfony HttpClient lets up to
            // `max_host_connections` of them be in flight at once; calling
            // getContent() on each then drives the multiplexed event loop.
            $responses = [];
            for ($j = 0; $j < $batchSize; $j++) {
                $responses[] = $client->request('GET', $url, $requestOptions);
            }

            foreach ($responses as $idx => $response) {
                $iterIndex = $completed + $idx;
                try {
                    $response->getContent(false);
                    $status = $response->getStatusCode();
                    // getInfo('total_time') is the curl-measured request time
                    // in seconds; reliable even when many responses overlap.
                    $elapsedMs = ((float) $response->getInfo('total_time')) * 1000.0;
                } catch (TransportExceptionInterface $e) {
                    $errors++;
                    $statusHistogram['transport_error'] = ($statusHistogram['transport_error'] ?? 0) + 1;
                    if ($firstErrorIter === null) {
                        $firstErrorIter   = $iterIndex;
                        $firstErrorStatus = 'transport: ' . $e->getMessage();
                    }
                    $logger->warning('benchmark.transport_error', [
                        'run_id'    => $runId,
                        'iteration' => $iterIndex,
                        'exception' => $e::class,
                        'message'   => $e->getMessage(),
                    ]);
                    continue;
                }

                $statusHistogram[$status] = ($statusHistogram[$status] ?? 0) + 1;

                $cacheHeader = $response->getHeaders(false)['x-cache'][0] ?? null;
                $cacheKey = $cacheHeader !== null && isset($cacheHistogram[$cacheHeader])
                    ? $cacheHeader
                    : ($cacheHeader === null ? 'absent' : 'unknown');
                $cacheHistogram[$cacheKey]++;

                if ($status < 200 || $status >= 300) {
                    $errors++;
                    if ($firstErrorIter === null) {
                        $firstErrorIter   = $iterIndex;
                        $firstErrorStatus = (string) $status;
                    }
                    $debugException = $response->getHeaders(false)['x-debug-exception'][0] ?? null;
                    $logger->warning('benchmark.non_2xx', [
                        'run_id'          => $runId,
                        'iteration'       => $iterIndex,
                        'status'          => $status,
                        'elapsed_ms'      => $elapsedMs,
                        'x_debug_exception' => $debugException !== null ? rawurldecode($debugException) : null,
                    ]);
                    continue;
                }
                $samples[] = $elapsedMs;
            }

            $completed += $batchSize;
        }

        $wallSeconds = (hrtime(true) - $wallStart) / 1_000_000_000.0;

        ksort($statusHistogram);
        $histogramStr = implode(', ', array_map(
            static fn($k, $v) => "$k=$v",
            array_keys($statusHistogram),
            array_values($statusHistogram)
        ));

        $metrics = new PerformanceMetrics($samples, $errors);

        $logger->info('benchmark.complete', [
            'run_id'             => $runId,
            'url'                => $url,
            'iterations'         => $iterations,
            'concurrency'        => $concurrency,
            'warmup'             => $warmup,
            'bypass_cache'       => $bypassCache,
            'count'              => $metrics->count(),
            'errors'             => $metrics->errors(),
            'first_error_iter'   => $firstErrorIter,
            'first_error_status' => $firstErrorStatus,
            'min_ms'             => $metrics->min(),
            'p50_ms'             => $metrics->p50(),
            'p95_ms'             => $metrics->p95(),
            'p99_ms'             => $metrics->p99(),
            'max_ms'             => $metrics->max(),
            'mean_ms'            => $metrics->mean(),
            'throughput_rps'     => $metrics->throughput($wallSeconds),
            'wall_seconds'       => $wallSeconds,
            'status_histogram'   => $statusHistogram,
            'cache_histogram'    => $cacheHistogram,
        ]);

        // When the harness is run with Redis active (bench-with-redis), the
        // controller must actually serve cache hits — otherwise every request
        // silently falls through to Postgres and the "upper limit" we measure
        // is the same Postgres ceiling bench-without-redis already finds. Fail
        // loudly with the cache breakdown so the operator can fix the Redis
        // connectivity (REDIS_HOST resolution, container networking, etc.).
        if (!$bypassCache && $cacheHistogram['hit'] === 0) {
            self::fail(sprintf(
                'bench-with-redis requested, but the controller never returned x-cache: hit '
                . '(histogram: hit=%d, miss=%d, bypass=%d, error=%d, unknown=%d, absent=%d). '
                . 'The app cannot serve cached responses on this stack — most likely the '
                . 'service container cannot reach Redis at the configured REDIS_HOST. '
                . 'Investigate before relying on these numbers as a Redis-served upper limit.',
                $cacheHistogram['hit'],
                $cacheHistogram['miss'],
                $cacheHistogram['bypass'],
                $cacheHistogram['error'],
                $cacheHistogram['unknown'],
                $cacheHistogram['absent']
            ));
        }

        self::assertSame(
            0,
            $errors,
            sprintf(
                '%d of %d requests failed against %s; first non-2xx at iteration %s (status %s); status histogram: %s; cache histogram: %s',
                $errors,
                $iterations,
                $url,
                $firstErrorIter ?? 'n/a',
                $firstErrorStatus ?? 'n/a',
                $histogramStr,
                json_encode($cacheHistogram)
            )
        );
        self::assertNotEmpty($samples, 'No timed samples were collected');

        $output = new StreamOutput(STDERR);
        $table = new Table($output);
        $table->setHeaders(['Metric', 'Value']);
        $table->setRows([
            ['URL',              $url],
            ['Iterations',       (string) $metrics->count()],
            ['Concurrency',      (string) $concurrency],
            ['Cache bypass',     $bypassCache ? 'yes (x-benchmark)' : 'no (Redis active)'],
            ['Timeout (s)',      (string) $timeoutSec],
            ['Warmup',           (string) $warmup],
            ['Errors',           (string) $metrics->errors()],
            ['Min (ms)',         number_format($metrics->min(), 2)],
            ['p50 (ms)',         number_format($metrics->p50(), 2)],
            ['p95 (ms)',         number_format($metrics->p95(), 2)],
            ['p99 (ms)',         number_format($metrics->p99(), 2)],
            ['Max (ms)',         number_format($metrics->max(), 2)],
            ['Mean (ms)',        number_format($metrics->mean(), 2)],
            ['Throughput (rps)', number_format($metrics->throughput($wallSeconds), 2)],
            ['Cache hits',       sprintf(
                'hit=%d miss=%d bypass=%d error=%d',
                $cacheHistogram['hit'],
                $cacheHistogram['miss'],
                $cacheHistogram['bypass'],
                $cacheHistogram['error']
            )],
        ]);
        $table->render();
    }
}
