import { Module } from '@nestjs/common';
import { APP_GUARD } from '@nestjs/core';
import type Redis from 'ioredis';
import { RedisModule } from '@/adapters/persistence/redis/redis.module';
import { REDIS_CLIENT } from '@/adapters/persistence/redis/redis.tokens';
import { DbModule } from '@/adapters/persistence/drizzle/db.module';
import { DB } from '@/adapters/persistence/drizzle/db.tokens';
import type { Db } from '@/adapters/persistence/drizzle/db.module';
import { DrizzleMembersRepository } from '@/adapters/persistence/drizzle/drizzle-members.repository';
import { MEMBERS_REPOSITORY, MembersRepository } from '@/core/members/members.repository';
import { ACCESS_TOKEN_STORE, AccessTokenStore } from '@/core/auth/access-token.store';
import { RedisAccessTokenStore } from '@/adapters/persistence/redis/redis-access-token.store';
import { AccessTokenMinter } from '@/core/auth/access-token.minter';
import { BasicCredentialsExtractor } from '@/core/auth/basic-credentials.extractor';
import { BearerGuard } from './bearer.guard';
import { TokenController } from './token.controller';

@Module({
  imports: [DbModule, RedisModule],
  controllers: [TokenController],
  providers: [
    {
      provide: ACCESS_TOKEN_STORE,
      useFactory: (redis: Redis) => new RedisAccessTokenStore(redis),
      inject: [REDIS_CLIENT],
    },
    {
      provide: AccessTokenMinter,
      useFactory: (store: AccessTokenStore) => new AccessTokenMinter(store, 900),
      inject: [ACCESS_TOKEN_STORE],
    },
    {
      provide: MEMBERS_REPOSITORY,
      useFactory: (db: Db) => new DrizzleMembersRepository(db),
      inject: [DB],
    },
    {
      provide: BasicCredentialsExtractor,
      useFactory: (members: MembersRepository) => new BasicCredentialsExtractor(members),
      inject: [MEMBERS_REPOSITORY],
    },
    BearerGuard,
    { provide: APP_GUARD, useClass: BearerGuard },
  ],
  exports: [ACCESS_TOKEN_STORE, AccessTokenMinter, BearerGuard],
})
export class AuthModule {}
