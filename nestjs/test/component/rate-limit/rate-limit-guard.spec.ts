import { Reflector } from '@nestjs/core';
import { RateLimitGuard } from '@/adapters/nestjs/rate-limit/rate-limit.guard';
import type { RateLimiter, Policy } from '@/core/rate-limit/rate-limiter';

function stubLimiter(accept: boolean, retry = 1): RateLimiter {
  return {
    consume: jest.fn(async (_p: Policy) => ({
      accepted: accept, limit: 60, remaining: accept ? 59 : 0, retryAfter: retry, reset: retry,
    })),
  } as never;
}

function ctxFor(path: string, ip = '203.0.113.5') {
  const req = { url: path, ip, method: 'GET', headers: {} };
  const headers: Record<string, string> = {};
  const res = { setHeader: (k: string, v: string) => { headers[k] = v; }, status: jest.fn().mockReturnThis(), json: jest.fn() };
  const ctx = {
    switchToHttp: () => ({ getRequest: () => req, getResponse: () => res }),
    getHandler: () => () => undefined, getClass: () => class {},
  };
  return { ctx, req, res, headers };
}

const env = { RATE_LIMIT_ENABLED: true, APP_ENV: 'test' };

describe('RateLimitGuard', () => {
  it('returns true and bypasses limiter for /api/healthcheck', async () => {
    const limiter = stubLimiter(true);
    const guard = new RateLimitGuard(env as never, limiter, new Reflector());
    const { ctx } = ctxFor('/api/healthcheck');
    await expect(guard.canActivate(ctx as never)).resolves.toBe(true);
    expect(limiter.consume).not.toHaveBeenCalled();
  });

  it('returns true when RATE_LIMIT_ENABLED is false', async () => {
    const limiter = stubLimiter(false);
    const guard = new RateLimitGuard({ RATE_LIMIT_ENABLED: false, APP_ENV: 'test' } as never, limiter, new Reflector());
    const { ctx } = ctxFor('/api/highlights');
    await expect(guard.canActivate(ctx as never)).resolves.toBe(true);
  });

  it('throws HttpException(429) and sets Retry-After when limiter rejects', async () => {
    const limiter = stubLimiter(false, 5);
    const guard = new RateLimitGuard(env as never, limiter, new Reflector());
    const { ctx, headers } = ctxFor('/api/highlights');
    await expect(guard.canActivate(ctx as never)).rejects.toMatchObject({
      status: 429,
      response: expect.objectContaining({
        type: 'https://tools.ietf.org/html/rfc6585#section-4',
        title: 'Too Many Requests',
        status: 429,
      }),
    });
    expect(headers['Retry-After']).toBe('5');
    expect(headers['RateLimit-Remaining']).toBe('0');
  });

  it('returns true and sets RateLimit headers when accepted', async () => {
    const limiter = stubLimiter(true);
    const guard = new RateLimitGuard(env as never, limiter, new Reflector());
    const { ctx, headers } = ctxFor('/api/highlights');
    await expect(guard.canActivate(ctx as never)).resolves.toBe(true);
    expect(headers['RateLimit-Limit']).toBe('60');
    expect(headers['RateLimit-Remaining']).toBe('59');
  });

  it('fails open when limiter throws', async () => {
    const limiter = { consume: jest.fn().mockRejectedValue(new Error('boom')) } as never;
    const guard = new RateLimitGuard(env as never, limiter, new Reflector());
    const { ctx } = ctxFor('/api/highlights');
    await expect(guard.canActivate(ctx as never)).resolves.toBe(true);
  });

  it('selects /api/token policy', async () => {
    const limiter = stubLimiter(true);
    const guard = new RateLimitGuard(env as never, limiter, new Reflector());
    const { ctx } = ctxFor('/api/token');
    await guard.canActivate(ctx as never);
    expect(limiter.consume).toHaveBeenCalledWith(
      expect.objectContaining({ kind: 'token-bucket', name: 'token_mint' }),
      expect.stringMatching(/^ip[46]:/),
    );
  });

  it('selects /api/docs policy', async () => {
    const limiter = stubLimiter(true);
    const guard = new RateLimitGuard(env as never, limiter, new Reflector());
    const { ctx } = ctxFor('/api/docs');
    await guard.canActivate(ctx as never);
    expect(limiter.consume).toHaveBeenCalledWith(
      expect.objectContaining({ kind: 'sliding-window', name: 'docs', limit: 30 }),
      expect.any(String),
    );
  });

  it('truncates IPv6 client IP to /64', async () => {
    const limiter = stubLimiter(true);
    const guard = new RateLimitGuard(env as never, limiter, new Reflector());
    const { ctx } = ctxFor('/api/highlights', '2001:db8:1234:5678:9abc:def0:1234:5678');
    await guard.canActivate(ctx as never);
    expect(limiter.consume).toHaveBeenCalledWith(expect.anything(), 'ip6:2001:db8:1234:5678');
  });
});
