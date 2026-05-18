import { createHash } from 'node:crypto';
import { InMemoryAccessTokenStore } from '@test/doubles/in-memory-access-token.store';

const sha256 = (s: string) => createHash('sha256').update(s).digest('hex');

describe('InMemoryAccessTokenStore', () => {
  it('put then resolve returns record for member', async () => {
    const store = new InMemoryAccessTokenStore();
    await store.put('plaintext-abc', '42', 900);
    const record = await store.resolve('plaintext-abc');
    expect(record?.memberId).toBe('42');
  });

  it('resolve returns null for unknown token', async () => {
    const store = new InMemoryAccessTokenStore();
    expect(await store.resolve('never-stored')).toBeNull();
  });

  it('resolve returns null after revoke', async () => {
    const store = new InMemoryAccessTokenStore();
    await store.put('plaintext-xyz', '42', 900);
    await store.revoke('plaintext-xyz');
    expect(await store.resolve('plaintext-xyz')).toBeNull();
  });

  it('resolve returns null for expired record', async () => {
    const store = new InMemoryAccessTokenStore();
    await store.put('plaintext-expired', '42', -1);
    expect(await store.resolve('plaintext-expired')).toBeNull();
  });

  it('uses sha256 keying internally', async () => {
    const store = new InMemoryAccessTokenStore();
    await store.put('plaintext-key-check', '42', 900);
    expect(store.hasKey(sha256('plaintext-key-check'))).toBe(true);
    expect(store.hasKey('plaintext-key-check')).toBe(false);
  });
});
