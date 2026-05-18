import { Reflector } from '@nestjs/core';
import { UnauthorizedException } from '@nestjs/common';
import { BearerGuard } from '@/auth/bearer.guard';
import { InMemoryAccessTokenStore } from '@test/doubles/in-memory-access-token.store';
import { createMember } from '@/members/member.entity';
import type { MembersRepository } from '@/members/members.repository';

class StubRepo {
  constructor(private member: ReturnType<typeof createMember> | null) {}
  async findById() { return this.member; }
}

function makeCtx(headers: Record<string, string>, isPublic = false) {
  const req: { headers: Record<string, string>; user?: unknown } = { headers };
  const reflector = { get: jest.fn().mockReturnValue(isPublic) } as unknown as Reflector;
  const ctx = {
    switchToHttp: () => ({ getRequest: () => req }),
    getHandler: () => () => undefined,
    getClass: () => class {},
  };
  return { ctx, req, reflector };
}

describe('BearerGuard', () => {
  it('returns true for routes decorated with @Public', async () => {
    const store = new InMemoryAccessTokenStore();
    const repo = new StubRepo(null) as unknown as MembersRepository;
    const { ctx, reflector } = makeCtx({}, true);
    const guard = new BearerGuard(reflector, store, repo);
    await expect(guard.canActivate(ctx as never)).resolves.toBe(true);
  });

  it('returns true and attaches member for active token', async () => {
    const store = new InMemoryAccessTokenStore();
    await store.put('plaintext-abc', '42', 900);
    const member = createMember({ id: 42, username: 'alice', isEnabled: true });
    const repo = new StubRepo(member) as unknown as MembersRepository;
    const { ctx, req, reflector } = makeCtx({ authorization: 'Bearer plaintext-abc' });
    const guard = new BearerGuard(reflector, store, repo);
    await expect(guard.canActivate(ctx as never)).resolves.toBe(true);
    expect(req.user).toBe(member);
  });

  it('throws for unknown token', async () => {
    const store = new InMemoryAccessTokenStore();
    const repo = new StubRepo(null) as unknown as MembersRepository;
    const { ctx, reflector } = makeCtx({ authorization: 'Bearer never-minted' });
    const guard = new BearerGuard(reflector, store, repo);
    await expect(guard.canActivate(ctx as never)).rejects.toBeInstanceOf(UnauthorizedException);
  });

  it('throws for expired token', async () => {
    const store = new InMemoryAccessTokenStore();
    await store.put('expired-abc', '42', -1);
    const member = createMember({ id: 42, isEnabled: true });
    const repo = new StubRepo(member) as unknown as MembersRepository;
    const { ctx, reflector } = makeCtx({ authorization: 'Bearer expired-abc' });
    const guard = new BearerGuard(reflector, store, repo);
    await expect(guard.canActivate(ctx as never)).rejects.toBeInstanceOf(UnauthorizedException);
  });

  it('throws when member is disabled', async () => {
    const store = new InMemoryAccessTokenStore();
    await store.put('plaintext-disabled', '42', 900);
    const member = createMember({ id: 42, isEnabled: false });
    const repo = new StubRepo(member) as unknown as MembersRepository;
    const { ctx, reflector } = makeCtx({ authorization: 'Bearer plaintext-disabled' });
    const guard = new BearerGuard(reflector, store, repo);
    await expect(guard.canActivate(ctx as never)).rejects.toBeInstanceOf(UnauthorizedException);
  });

  it('throws when Authorization header is missing or non-Bearer', async () => {
    const store = new InMemoryAccessTokenStore();
    const repo = new StubRepo(null) as unknown as MembersRepository;
    const { ctx: ctxA, reflector } = makeCtx({});
    const guard = new BearerGuard(reflector, store, repo);
    await expect(guard.canActivate(ctxA as never)).rejects.toBeInstanceOf(UnauthorizedException);
    const { ctx: ctxB } = makeCtx({ authorization: 'Basic abcd' });
    await expect(guard.canActivate(ctxB as never)).rejects.toBeInstanceOf(UnauthorizedException);
  });
});
