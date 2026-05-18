import { Module } from '@nestjs/common';
import { APP_GUARD } from '@nestjs/core';
import type Redis from 'ioredis';
import { MembersModule } from '@/members/members.module';
import { RedisModule } from '@/redis/redis.module';
import { REDIS_CLIENT } from '@/redis/redis.tokens';
import { MembersRepository } from '@/members/members.repository';
import { ACCESS_TOKEN_STORE, AccessTokenStore } from './access-token.store';
import { RedisAccessTokenStore } from './redis-access-token.store';
import { AccessTokenMinter } from './access-token.minter';
import { BasicCredentialsExtractor } from './basic-credentials.extractor';
import { BearerGuard } from './bearer.guard';
import { TokenController } from './token.controller';

@Module({
  imports: [MembersModule, RedisModule],
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
      provide: BasicCredentialsExtractor,
      useFactory: (members: MembersRepository) => new BasicCredentialsExtractor(members),
      inject: [MembersRepository],
    },
    BearerGuard,
    { provide: APP_GUARD, useClass: BearerGuard },
  ],
  exports: [ACCESS_TOKEN_STORE, AccessTokenMinter, BearerGuard],
})
export class AuthModule {}
