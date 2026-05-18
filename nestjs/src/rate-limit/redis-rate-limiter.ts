import type Redis from 'ioredis';

export type Policy =
  | { kind: 'sliding-window'; name: string; limit: number; windowMs: number }
  | { kind: 'token-bucket'; name: string; burst: number; refillAmount: number; intervalMs: number };

export interface ConsumeResult {
  accepted: boolean;
  limit: number;
  remaining: number;
  retryAfter: number;
  reset: number;
}

const SLIDING_WINDOW_LUA = `
local key = KEYS[1]
local now = tonumber(ARGV[1])
local windowMs = tonumber(ARGV[2])
local limit = tonumber(ARGV[3])
redis.call('ZREMRANGEBYSCORE', key, '-inf', now - windowMs)
local n = redis.call('ZCARD', key)
if n >= limit then
  local oldest = redis.call('ZRANGE', key, 0, 0, 'WITHSCORES')
  local retry = math.max(1, math.ceil((tonumber(oldest[2]) + windowMs - now) / 1000))
  return {0, limit, retry}
end
redis.call('ZADD', key, now, now .. '-' .. math.random(1000000))
redis.call('PEXPIRE', key, windowMs)
return {1, limit, 0}
`.trim();

const TOKEN_BUCKET_LUA = `
local key = KEYS[1]
local now = tonumber(ARGV[1])
local burst = tonumber(ARGV[2])
local refillAmount = tonumber(ARGV[3])
local intervalMs = tonumber(ARGV[4])
local data = redis.call('HMGET', key, 'tokens', 'last')
local tokens = tonumber(data[1])
local last = tonumber(data[2])
if tokens == nil then tokens = burst end
if last == nil then last = now end
local elapsed = math.max(0, now - last)
local refilled = math.floor((elapsed / intervalMs) * refillAmount)
tokens = math.min(burst, tokens + refilled)
last = now
if tokens < 1 then
  local retry = math.max(1, math.ceil((intervalMs - (elapsed % intervalMs)) / 1000))
  redis.call('HMSET', key, 'tokens', tokens, 'last', last)
  redis.call('PEXPIRE', key, intervalMs * 2)
  return {0, burst, retry}
end
tokens = tokens - 1
redis.call('HMSET', key, 'tokens', tokens, 'last', last)
redis.call('PEXPIRE', key, intervalMs * 2)
return {1, burst, 0}
`.trim();

export class RedisRateLimiter {
  constructor(private readonly redis: Redis) {}

  async consume(policy: Policy, key: string): Promise<ConsumeResult> {
    const now = Date.now();
    if (policy.kind === 'sliding-window') {
      const fullKey = `rl:sw:${policy.name}:${key}`;
      const [accepted, limit, retryAfter] = (await this.redis.eval(
        SLIDING_WINDOW_LUA, 1, fullKey, String(now), String(policy.windowMs), String(policy.limit),
      )) as [number, number, number];
      const remaining = accepted === 1 ? Math.max(0, limit - 1) : 0;
      return { accepted: accepted === 1, limit, remaining, retryAfter, reset: retryAfter };
    }
    const fullKey = `rl:tb:${policy.name}:${key}`;
    const [accepted, burst, retryAfter] = (await this.redis.eval(
      TOKEN_BUCKET_LUA, 1, fullKey, String(now), String(policy.burst), String(policy.refillAmount), String(policy.intervalMs),
    )) as [number, number, number];
    return {
      accepted: accepted === 1,
      limit: burst,
      remaining: accepted === 1 ? burst - 1 : 0,
      retryAfter,
      reset: retryAfter,
    };
  }
}
