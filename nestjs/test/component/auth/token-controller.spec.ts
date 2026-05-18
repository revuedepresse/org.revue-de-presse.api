import { UnauthorizedException } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import { TokenController } from '@/auth/token.controller';
import { AccessTokenMinter } from '@/auth/access-token.minter';
import { BasicCredentialsExtractor } from '@/auth/basic-credentials.extractor';
import { ACCESS_TOKEN_STORE } from '@/auth/access-token.store';
import { InMemoryAccessTokenStore } from '@test/doubles/in-memory-access-token.store';
import { MembersRepository } from '@/members/members.repository';
import { createMember } from '@/members/member.entity';

class StubRepo {
  constructor(private expected: string, private member: ReturnType<typeof createMember> | null) {}
  async findEnabledByApiKey(s: string) { return s === this.expected ? this.member : null; }
}

const basic = (s: string) => 'Basic ' + Buffer.from(s).toString('base64');

describe('TokenController', () => {
  async function setup(member: ReturnType<typeof createMember> | null, expected = 'secret') {
    const store = new InMemoryAccessTokenStore();
    const repo = new StubRepo(expected, member) as unknown as MembersRepository;
    const moduleRef = await Test.createTestingModule({
      controllers: [TokenController],
      providers: [
        { provide: ACCESS_TOKEN_STORE, useValue: store },
        {
          provide: AccessTokenMinter,
          useValue: new AccessTokenMinter(store, 900),
        },
        {
          provide: BasicCredentialsExtractor,
          useValue: new BasicCredentialsExtractor(repo),
        },
      ],
    }).compile();
    return moduleRef.get(TokenController);
  }

  it('returns AccessTokenResponseDto for valid credentials', async () => {
    const member = createMember({ id: 42, apiKey: 'secret', isEnabled: true });
    const controller = await setup(member);
    const dto = await controller.mint({ authorization: basic(':secret') });
    expect(dto.token_type).toBe('Bearer');
    expect(dto.expires_in).toBe(900);
    expect(dto.access_token).toMatch(/^[0-9a-f]{64}$/);
  });

  it('throws UnauthorizedException for invalid credentials', async () => {
    const controller = await setup(null);
    await expect(controller.mint({ authorization: basic(':wrong') })).rejects.toBeInstanceOf(UnauthorizedException);
  });
});
