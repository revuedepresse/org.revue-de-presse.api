import { randomBytes } from 'node:crypto';
import { AccessTokenStore } from './access-token.store';

export class AccessTokenMinter {
  constructor(
    private readonly store: AccessTokenStore,
    private readonly ttlSeconds: number = 900,
  ) {}

  async mint(memberId: string): Promise<string> {
    const token = randomBytes(32).toString('hex');
    await this.store.put(token, memberId, this.ttlSeconds);
    return token;
  }

  ttl(): number { return this.ttlSeconds; }
}
