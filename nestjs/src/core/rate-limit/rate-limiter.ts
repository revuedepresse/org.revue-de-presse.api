export const RATE_LIMITER = Symbol('RATE_LIMITER');

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

export interface RateLimiter {
  consume(policy: Policy, key: string): Promise<ConsumeResult>;
}
