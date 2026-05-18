import { createHash } from 'node:crypto';
import { RedisAccessTokenStore } from '@/auth/redis-access-token.store';
import { InMemoryRedis } from '@test/doubles/in-memory-redis';

const sha256 = (s: string) => createHash('sha256').update(s).digest('hex');

describe('RedisAccessTokenStore', () => {
  it('put calls setex on sha256 key with member id payload', async () => {
    const redis = new InMemoryRedis();
    const store = new RedisAccessTokenStore(redis as never);
    await store.put('plaintext-token', '42', 900);
    const key = 'auth:bearer:' + sha256('plaintext-token');
    const raw = await redis.get(key);
    expect(raw).not.toBeNull();
    const payload = JSON.parse(raw!);
    expect(payload.m).toBe('42');
    expect(typeof payload.i).toBe('number');
    expect(typeof payload.e).toBe('number');
  });

  it('resolve returns record for active token', async () => {
    const redis = new InMemoryRedis();
    const store = new RedisAccessTokenStore(redis as never);
    await store.put('plaintext-token', '42', 900);
    const record = await store.resolve('plaintext-token');
    expect(record?.memberId).toBe('42');
  });

  it('resolve returns null for unknown token', async () => {
    const store = new RedisAccessTokenStore(new InMemoryRedis() as never);
    expect(await store.resolve('never-stored')).toBeNull();
  });

  it('resolve returns null when record `e` is in the past', async () => {
    const redis = new InMemoryRedis();
    const store = new RedisAccessTokenStore(redis as never);
    const key = 'auth:bearer:' + sha256('expired-token');
    const now = Math.floor(Date.now() / 1000);
    await redis.set(key, JSON.stringify({ m: '42', i: now - 1000, e: now - 1 }));
    expect(await store.resolve('expired-token')).toBeNull();
  });

  it('revoke deletes sha256-keyed entry', async () => {
    const redis = new InMemoryRedis();
    const store = new RedisAccessTokenStore(redis as never);
    await store.put('plaintext-token', '42', 900);
    await store.revoke('plaintext-token');
    const key = 'auth:bearer:' + sha256('plaintext-token');
    expect(await redis.get(key)).toBeNull();
  });
});
