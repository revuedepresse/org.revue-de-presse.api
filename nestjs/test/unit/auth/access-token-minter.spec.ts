import { AccessTokenMinter } from '@/core/auth/access-token.minter';
import { InMemoryAccessTokenStore } from '@test/doubles/in-memory-access-token.store';

describe('AccessTokenMinter', () => {
  it('returns 64-hex token', async () => {
    const store = new InMemoryAccessTokenStore();
    const minter = new AccessTokenMinter(store, 900);
    const token = await minter.mint('42');
    expect(token).toMatch(/^[0-9a-f]{64}$/);
  });

  it('persists token in store with TTL', async () => {
    const store = new InMemoryAccessTokenStore();
    const minter = new AccessTokenMinter(store, 900);
    const token = await minter.mint('42');
    expect(await store.resolve(token)).not.toBeNull();
  });

  it('each call returns a distinct token', async () => {
    const store = new InMemoryAccessTokenStore();
    const minter = new AccessTokenMinter(store, 900);
    const tokens = await Promise.all([minter.mint('42'), minter.mint('42'), minter.mint('42')]);
    expect(new Set(tokens).size).toBe(3);
  });
});
