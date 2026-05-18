import { AccessTokenRecord } from '@/auth/access-token-record';

describe('AccessTokenRecord', () => {
  it('carries memberId, issuedAt, expiresAt', () => {
    const issued = new Date('2026-05-17T10:00:00Z');
    const expires = new Date('2026-05-17T10:15:00Z');
    const record = new AccessTokenRecord('42', issued, expires);
    expect(record.memberId).toBe('42');
    expect(record.issuedAt).toBe(issued);
    expect(record.expiresAt).toBe(expires);
  });

  it('isExpired returns true when expiresAt in the past', () => {
    const past = new Date(Date.now() - 60_000);
    const r = new AccessTokenRecord('42', new Date(Date.now() - 900_000), past);
    expect(r.isExpired()).toBe(true);
  });

  it('isExpired returns false when expiresAt in the future', () => {
    const future = new Date(Date.now() + 300_000);
    const r = new AccessTokenRecord('42', new Date(Date.now() - 600_000), future);
    expect(r.isExpired()).toBe(false);
  });
});
