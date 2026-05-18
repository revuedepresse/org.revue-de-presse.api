import { MembersRepository } from '@/members/members.repository';
import type { Member } from '@/members/member.entity';
import { InvalidClientCredentialsException } from './invalid-client-credentials.exception';

type HeadersLike = Record<string, string | string[] | undefined>;

export class BasicCredentialsExtractor {
  constructor(private readonly members: MembersRepository) {}

  async extract(headers: HeadersLike): Promise<Member> {
    const raw = headers.authorization;
    const header = Array.isArray(raw) ? raw[0] : raw;
    if (typeof header !== 'string' || !header.startsWith('Basic ')) {
      throw new InvalidClientCredentialsException('Missing or non-Basic Authorization header');
    }

    let decoded: string;
    try {
      decoded = Buffer.from(header.slice(6), 'base64').toString('utf8');
      // Round-trip detection of malformed base64: re-encode and compare with original input.
      const reencoded = Buffer.from(decoded, 'utf8').toString('base64');
      if (reencoded.replace(/=+$/, '') !== header.slice(6).replace(/=+$/, '')) {
        throw new Error('malformed base64');
      }
    } catch {
      throw new InvalidClientCredentialsException('Malformed Basic credentials');
    }
    if (!decoded.includes(':')) {
      throw new InvalidClientCredentialsException('Malformed Basic credentials');
    }
    const submitted = decoded.slice(decoded.indexOf(':') + 1);
    if (submitted === '') {
      throw new InvalidClientCredentialsException('Empty client secret');
    }
    const match = await this.members.findEnabledByApiKey(submitted);
    if (!match) throw new InvalidClientCredentialsException('Invalid client credentials');
    return match;
  }
}
