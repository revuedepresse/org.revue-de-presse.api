import { AccessTokenRecord } from './access-token-record';

export const ACCESS_TOKEN_STORE = Symbol('ACCESS_TOKEN_STORE');

export interface AccessTokenStore {
  put(tokenPlaintext: string, memberId: string, ttlSeconds: number): Promise<void>;
  resolve(tokenPlaintext: string): Promise<AccessTokenRecord | null>;
  revoke(tokenPlaintext: string): Promise<void>;
}
