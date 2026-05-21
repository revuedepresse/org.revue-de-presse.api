import type { Member } from './member.entity';

export const MEMBERS_REPOSITORY = Symbol('MEMBERS_REPOSITORY');

export interface MembersRepository {
  findEnabledByApiKey(secret: string): Promise<Member | null>;
  findById(id: number): Promise<Member | null>;
}
