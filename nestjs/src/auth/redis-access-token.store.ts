import { createHash } from 'node:crypto';
import { Inject, Injectable } from '@nestjs/common';
import type Redis from 'ioredis';
import { REDIS_CLIENT } from '@/redis/redis.tokens';
import { AccessTokenRecord } from './access-token-record';
import { AccessTokenStore } from './access-token.store';

const sha256 = (s: string) => createHash('sha256').update(s).digest('hex');

@Injectable()
export class RedisAccessTokenStore implements AccessTokenStore {
  private static readonly PREFIX = 'auth:bearer:';

  constructor(@Inject(REDIS_CLIENT) private readonly redis: Redis) {}

  async put(tokenPlaintext: string, memberId: string, ttlSeconds: number): Promise<void> {
    const now = Math.floor(Date.now() / 1000);
    const payload = JSON.stringify({ m: memberId, i: now, e: now + ttlSeconds });
    await this.redis.setex(this.key(tokenPlaintext), ttlSeconds, payload);
  }

  async resolve(tokenPlaintext: string): Promise<AccessTokenRecord | null> {
    const raw = await this.redis.get(this.key(tokenPlaintext));
    if (!raw) return null;
    let parsed: unknown;
    try { parsed = JSON.parse(raw); } catch { return null; }
    if (!parsed || typeof parsed !== 'object') return null;
    const data = parsed as Record<string, unknown>;
    if (typeof data.m !== 'string' || typeof data.i !== 'number' || typeof data.e !== 'number') return null;
    const expiresAt = new Date(data.e * 1000);
    if (expiresAt.getTime() <= Date.now()) return null;
    return new AccessTokenRecord(data.m, new Date(data.i * 1000), expiresAt);
  }

  async revoke(tokenPlaintext: string): Promise<void> {
    await this.redis.del(this.key(tokenPlaintext));
  }

  private key(token: string): string {
    return RedisAccessTokenStore.PREFIX + sha256(token);
  }
}
