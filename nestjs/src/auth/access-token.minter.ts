import { randomBytes } from 'node:crypto';
import { Inject, Injectable } from '@nestjs/common';
import { ACCESS_TOKEN_STORE, AccessTokenStore } from './access-token.store';

@Injectable()
export class AccessTokenMinter {
  constructor(
    @Inject(ACCESS_TOKEN_STORE) private readonly store: AccessTokenStore,
    private readonly ttlSeconds: number = 900,
  ) {}

  async mint(memberId: string): Promise<string> {
    const token = randomBytes(32).toString('hex');
    await this.store.put(token, memberId, this.ttlSeconds);
    return token;
  }

  ttl(): number { return this.ttlSeconds; }
}
