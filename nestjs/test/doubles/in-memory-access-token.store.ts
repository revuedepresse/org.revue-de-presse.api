import { createHash } from 'node:crypto';
import { AccessTokenRecord } from '@/core/auth/access-token-record';
import { AccessTokenStore } from '@/core/auth/access-token.store';

const sha256 = (s: string) => createHash('sha256').update(s).digest('hex');

export class InMemoryAccessTokenStore implements AccessTokenStore {
  private records = new Map<string, AccessTokenRecord>();

  async put(tokenPlaintext: string, memberId: string, ttlSeconds: number): Promise<void> {
    const key = sha256(tokenPlaintext);
    const now = new Date();
    this.records.set(
      key,
      new AccessTokenRecord(memberId, now, new Date(now.getTime() + ttlSeconds * 1000)),
    );
  }

  async resolve(tokenPlaintext: string): Promise<AccessTokenRecord | null> {
    const key = sha256(tokenPlaintext);
    const record = this.records.get(key) ?? null;
    if (!record) return null;
    if (record.isExpired()) { this.records.delete(key); return null; }
    return record;
  }

  async revoke(tokenPlaintext: string): Promise<void> {
    this.records.delete(sha256(tokenPlaintext));
  }

  hasKey(key: string): boolean { return this.records.has(key); }
}
