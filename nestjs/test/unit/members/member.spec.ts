import { createMember } from '@/core/members/member.entity';

describe('Member', () => {
  it('default isEnabled is false', () => {
    const m = createMember();
    expect(m.isEnabled).toBe(false);
  });

  it('username round-trip', () => {
    const m = createMember({ username: 'alice' });
    expect(m.username).toBe('alice');
  });

  it('apiKey round-trip', () => {
    const m = createMember({ apiKey: 'secret-123' });
    expect(m.apiKey).toBe('secret-123');
  });

  it('enabled round-trip', () => {
    const m = createMember({ isEnabled: true });
    expect(m.isEnabled).toBe(true);
  });

  it('userIdentifier returns username', () => {
    const m = createMember({ username: 'bob' });
    expect(m.username).toBe('bob');
  });
});
