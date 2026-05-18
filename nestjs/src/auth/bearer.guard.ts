import { CanActivate, ExecutionContext, Inject, Injectable, UnauthorizedException } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { ACCESS_TOKEN_STORE, AccessTokenStore } from '@/core/auth/access-token.store';
import { MEMBERS_REPOSITORY, MembersRepository } from '@/core/members/members.repository';
import { IS_PUBLIC } from './public.decorator';

@Injectable()
export class BearerGuard implements CanActivate {
  constructor(
    private readonly reflector: Reflector,
    @Inject(ACCESS_TOKEN_STORE) private readonly store: AccessTokenStore,
    @Inject(MEMBERS_REPOSITORY) private readonly members: MembersRepository,
  ) {}

  async canActivate(ctx: ExecutionContext): Promise<boolean> {
    const isPublic =
      this.reflector.get<boolean>(IS_PUBLIC, ctx.getHandler()) ||
      this.reflector.get<boolean>(IS_PUBLIC, ctx.getClass());
    if (isPublic) return true;

    const req = ctx.switchToHttp().getRequest<{ headers: Record<string, string>; user?: unknown }>();
    const header = req.headers['authorization'] ?? '';
    if (typeof header !== 'string' || !header.startsWith('Bearer ')) {
      throw new UnauthorizedException('Invalid or expired access token.');
    }
    const token = header.slice(7);
    const record = await this.store.resolve(token);
    if (!record) throw new UnauthorizedException('Invalid or expired access token.');

    const member = await this.members.findById(Number(record.memberId));
    if (!member || !member.isEnabled) throw new UnauthorizedException('Invalid or expired access token.');

    req.user = member;
    return true;
  }
}
