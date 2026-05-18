import { BasicCredentialsExtractor } from '@/core/auth/basic-credentials.extractor';
import { InvalidClientCredentialsError } from '@/core/errors/invalid-client-credentials.error';
import { createMember } from '@/core/members/member.entity';
import type { MembersRepository } from '@/core/members/members.repository';

class CountingRepo {
  findEnabledByApiKeyCalls = 0;
  constructor(private expected: string, private member: ReturnType<typeof createMember> | null) {}
  async findEnabledByApiKey(submitted: string) {
    this.findEnabledByApiKeyCalls++;
    return submitted === this.expected ? this.member : null;
  }
}

const asRepo = (r: CountingRepo) => r as unknown as MembersRepository;
const basic = (s: string) => 'Basic ' + Buffer.from(s).toString('base64');

describe('BasicCredentialsExtractor', () => {
  it('returns member for valid basic credentials', async () => {
    const member = createMember({ apiKey: 'secret-abc', isEnabled: true, username: 'u' });
    const repo = new CountingRepo('secret-abc', member);
    const ex = new BasicCredentialsExtractor(asRepo(repo));
    const result = await ex.extract({ authorization: basic(':secret-abc') });
    expect(result).toBe(member);
  });

  it('throws for missing authorization header', async () => {
    const ex = new BasicCredentialsExtractor(asRepo(new CountingRepo('any', null)));
    await expect(ex.extract({})).rejects.toBeInstanceOf(InvalidClientCredentialsError);
  });

  it('throws for non-Basic scheme', async () => {
    const ex = new BasicCredentialsExtractor(asRepo(new CountingRepo('any', null)));
    await expect(ex.extract({ authorization: 'Bearer some-token' })).rejects.toBeInstanceOf(InvalidClientCredentialsError);
  });

  it('throws for malformed base64', async () => {
    const ex = new BasicCredentialsExtractor(asRepo(new CountingRepo('any', null)));
    await expect(ex.extract({ authorization: 'Basic !!!!' })).rejects.toBeInstanceOf(InvalidClientCredentialsError);
  });

  it('throws for wrong secret', async () => {
    const member = createMember({ apiKey: 'right-secret', isEnabled: true });
    const ex = new BasicCredentialsExtractor(asRepo(new CountingRepo('right-secret', member)));
    await expect(ex.extract({ authorization: basic(':wrong-secret') })).rejects.toBeInstanceOf(InvalidClientCredentialsError);
  });

  it('repository lookup does not fan out — findEnabledByApiKey called once only', async () => {
    const member = createMember({ apiKey: 'secret-abc', isEnabled: true });
    const repo = new CountingRepo('secret-abc', member);
    await new BasicCredentialsExtractor(asRepo(repo)).extract({ authorization: basic(':secret-abc') });
    expect(repo.findEnabledByApiKeyCalls).toBe(1);
  });
});
