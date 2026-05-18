import { RedisRateLimiter } from '@/rate-limit/redis-rate-limiter';
import { InMemoryRedis } from '@test/doubles/in-memory-redis';

// Drop-in eval-stub that mimics the sliding-window contract for tests.
class WindowRedis extends InMemoryRedis {
  private counts = new Map<string, number[]>();
  async eval(_script: string, _n: number, key: string, nowMsStr: string, windowMsStr: string, limitStr: string): Promise<[number, number, number]> {
    const now = Number(nowMsStr); const windowMs = Number(windowMsStr); const limit = Number(limitStr);
    const arr = this.counts.get(key) ?? [];
    const fresh = arr.filter((t) => t > now - windowMs);
    if (fresh.length >= limit) return [0, limit, Math.max(1, Math.floor((fresh[0] + windowMs - now) / 1000))];
    fresh.push(now);
    this.counts.set(key, fresh);
    return [1, limit, 0];
  }
}

describe('RedisRateLimiter sliding window', () => {
  it('accepts up to the limit then rejects with retry-after', async () => {
    const redis = new WindowRedis();
    const limiter = new RedisRateLimiter(redis as never);
    const policy = { kind: 'sliding-window' as const, name: 'highlights', limit: 2, windowMs: 60_000 };
    const a = await limiter.consume(policy, 'ip4:203.0.113.5');
    expect(a.accepted).toBe(true);
    const b = await limiter.consume(policy, 'ip4:203.0.113.5');
    expect(b.accepted).toBe(true);
    const c = await limiter.consume(policy, 'ip4:203.0.113.5');
    expect(c.accepted).toBe(false);
    expect(c.retryAfter).toBeGreaterThan(0);
  });
});
