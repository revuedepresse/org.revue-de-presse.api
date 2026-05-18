import { CanActivate, ExecutionContext, HttpException, HttpStatus, Inject, Injectable, Logger } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import type { Request, Response } from 'express';
import { ENV } from '@/config/env';
import type { Env } from '@/config/env';
import { RedisRateLimiter, Policy } from './redis-rate-limiter';

const TOKEN_POLICY: Policy = { kind: 'token-bucket', name: 'token_mint', burst: 3, refillAmount: 1, intervalMs: 6000 };
const DOCS_POLICY: Policy = { kind: 'sliding-window', name: 'docs', limit: 30, windowMs: 60_000 };
const DEFAULT_POLICY: Policy = { kind: 'sliding-window', name: 'highlights', limit: 60, windowMs: 60_000 };

@Injectable()
export class RateLimitGuard implements CanActivate {
  private readonly logger = new Logger(RateLimitGuard.name);
  constructor(
    @Inject(ENV) private readonly env: Env,
    private readonly limiter: RedisRateLimiter,
    private readonly _reflector: Reflector,
  ) {}

  async canActivate(ctx: ExecutionContext): Promise<boolean> {
    if (!this.env.RATE_LIMIT_ENABLED) return true;
    const req = ctx.switchToHttp().getRequest<Request>();
    const res = ctx.switchToHttp().getResponse<Response>();
    const policy = this.selectPolicy(req.url);
    if (!policy) return true;

    const key = ipKey(req);
    let result;
    try {
      result = await this.limiter.consume(policy, key);
    } catch (err) {
      this.logger.warn({ msg: 'rate-limit-fail-open', error: (err as Error).message, path: req.url });
      return true;
    }

    res.setHeader('RateLimit-Limit', String(result.limit));
    res.setHeader('RateLimit-Remaining', String(result.remaining));
    res.setHeader('RateLimit-Reset', String(result.reset));

    if (!result.accepted) {
      res.setHeader('Retry-After', String(result.retryAfter));
      throw new HttpException(
        {
          type: 'https://tools.ietf.org/html/rfc6585#section-4',
          title: 'Too Many Requests',
          status: HttpStatus.TOO_MANY_REQUESTS,
          detail: `Rate limit exceeded for ${req.url}`,
        },
        HttpStatus.TOO_MANY_REQUESTS,
      );
    }
    return true;
  }

  private selectPolicy(url: string): Policy | null {
    const path = url.split('?')[0];
    if (path === '/api/healthcheck') return null;
    if (path === '/api/token') return TOKEN_POLICY;
    if (path.startsWith('/api/docs')) return DOCS_POLICY;
    if (path.startsWith('/api/')) return DEFAULT_POLICY;
    return null;
  }
}

function ipKey(req: Request): string {
  const ip = req.ip ?? '';
  if (!ip) return 'ip:unknown';
  if (ip.includes(':')) return 'ip6:' + ip.split(':').slice(0, 4).join(':');
  return 'ip4:' + ip;
}
