export interface Member {
  id: number | null;
  apiKey: string | null;
  username: string | null;
  usernameCanonical: string | null;
  isEnabled: boolean;
}

export function createMember(overrides: Partial<Member> = {}): Member {
  return {
    id: null,
    apiKey: null,
    username: null,
    usernameCanonical: null,
    isEnabled: false,
    ...overrides,
  };
}
