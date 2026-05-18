import { InMemoryRedis } from './in-memory-redis';

export class InMemoryRedisWithEval extends InMemoryRedis {
  private hashes = new Map<string, Map<string, string>>();
  private sets = new Map<string, Array<[number, string]>>();

  async eval(script: string, _n: number, ...args: string[]): Promise<unknown> {
    const isSliding = script.includes('ZADD');
    if (isSliding) return this.evalSliding(args);
    return this.evalTokenBucket(args);
  }

  private async evalSliding(args: string[]): Promise<[number, number, number]> {
    const [key, nowStr, windowStr, limitStr] = args;
    const now = Number(nowStr); const windowMs = Number(windowStr); const limit = Number(limitStr);
    const arr = (this.sets.get(key) ?? []).filter(([score]) => score > now - windowMs);
    if (arr.length >= limit) {
      const oldest = arr[0][0];
      return [0, limit, Math.max(1, Math.ceil((oldest + windowMs - now) / 1000))];
    }
    arr.push([now, `${now}-${Math.random()}`]);
    this.sets.set(key, arr);
    return [1, limit, 0];
  }

  private async evalTokenBucket(args: string[]): Promise<[number, number, number]> {
    const [key, nowStr, burstStr, refillStr, intervalStr] = args;
    const now = Number(nowStr); const burst = Number(burstStr);
    const refillAmount = Number(refillStr); const intervalMs = Number(intervalStr);
    const h = this.hashes.get(key) ?? new Map<string, string>();
    let tokens = Number(h.get('tokens') ?? burst);
    let last = Number(h.get('last') ?? now);
    const elapsed = Math.max(0, now - last);
    const refilled = Math.floor((elapsed / intervalMs) * refillAmount);
    tokens = Math.min(burst, tokens + refilled);
    last = now;
    if (tokens < 1) {
      h.set('tokens', String(tokens)); h.set('last', String(last));
      this.hashes.set(key, h);
      return [0, burst, Math.max(1, Math.ceil((intervalMs - (elapsed % intervalMs)) / 1000))];
    }
    tokens -= 1;
    h.set('tokens', String(tokens)); h.set('last', String(last));
    this.hashes.set(key, h);
    return [1, burst, 0];
  }
}
