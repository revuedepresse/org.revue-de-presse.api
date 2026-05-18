import { PerformanceMetrics } from '@/highlights/performance-metrics';

const HOST = process.env.BENCHMARK_HOST ?? '';
const SECRET = process.env.API_CLIENT_SECRET ?? process.env.API_AUTH_TOKEN ?? '';
const ITERATIONS = Number(process.env.BENCH_ITERATIONS ?? 50);
const WARMUP = Number(process.env.BENCH_WARMUP ?? 3);
const CONCURRENCY = Math.max(1, Number(process.env.BENCH_CONCURRENCY ?? 1));
const TIMEOUT = Number(process.env.BENCH_TIMEOUT ?? 30) * 1000;
const BYPASS_CACHE = (process.env.BENCH_BYPASS_CACHE ?? '1') !== '0';
const P95_CEILING_MS = Number(process.env.BENCH_P95_CEILING_MS ?? 1500);

(HOST === '' || SECRET === '' ? describe.skip : describe)('Highlights performance (e2e)', () => {
  it('p95 stays below the ceiling and no errors', async () => {
    const base = HOST.includes('://') ? HOST : `https://${HOST}`;
    const tokenResp = await fetch(`${base}/api/token`, {
      method: 'POST',
      headers: { Authorization: 'Basic ' + Buffer.from(':' + SECRET).toString('base64') },
    });
    const tokenBody = await tokenResp.json() as { access_token?: string };
    if (!tokenBody.access_token) throw new Error(`token mint failed: ${tokenResp.status}`);
    const bearer = tokenBody.access_token;

    const headers: Record<string, string> = { Authorization: 'Bearer ' + bearer };
    if (BYPASS_CACHE) headers['x-benchmark'] = '1';

    const url = `${base}/api/highlights?startDate=2024-01-01%2000:00:00&endDate=2024-01-01%2023:59:59&includeRetweets=0`;

    for (let i = 0; i < WARMUP; i++) {
      await fetch(url, { headers }).catch(() => undefined);
    }

    const samples: number[] = [];
    let errors = 0;
    const wallStart = process.hrtime.bigint();

    let done = 0;
    while (done < ITERATIONS) {
      const batch = Math.min(CONCURRENCY, ITERATIONS - done);
      const pairs = await Promise.all(
        Array.from({ length: batch }, async () => {
          const t0 = performance.now();
          try {
            const ctrl = AbortSignal.timeout(TIMEOUT);
            const r = await fetch(url, { headers, signal: ctrl });
            const ms = performance.now() - t0;
            return { ok: r.ok, ms };
          } catch { return { ok: false, ms: 0 }; }
        }),
      );
      for (const p of pairs) {
        if (p.ok) samples.push(p.ms); else errors += 1;
      }
      done += batch;
    }

    const wallSeconds = Number(process.hrtime.bigint() - wallStart) / 1e9;
    const m = new PerformanceMetrics(samples, errors);
    // eslint-disable-next-line no-console
    console.log(JSON.stringify({
      count: m.count(), errors: m.errors(),
      min_ms: m.min(), p50_ms: m.p50(), p95_ms: m.p95(), p99_ms: m.p99(), max_ms: m.max(),
      throughput_rps: m.throughput(wallSeconds),
    }));

    expect(errors).toBe(0);
    expect(m.count()).toBeGreaterThan(0);
    expect(m.p95()).toBeLessThanOrEqual(P95_CEILING_MS);
  }, 600_000);
});
