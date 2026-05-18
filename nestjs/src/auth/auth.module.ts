import { Module } from '@nestjs/common';
import { APP_GUARD } from '@nestjs/core';
import { MembersModule } from '@/members/members.module';
import { RedisModule } from '@/redis/redis.module';
import { ACCESS_TOKEN_STORE } from './access-token.store';
import { RedisAccessTokenStore } from './redis-access-token.store';
import { AccessTokenMinter } from './access-token.minter';
import { BasicCredentialsExtractor } from './basic-credentials.extractor';
import { BearerGuard } from './bearer.guard';
import { TokenController } from './token.controller';

@Module({
  imports: [MembersModule, RedisModule],
  controllers: [TokenController],
  providers: [
    { provide: ACCESS_TOKEN_STORE, useClass: RedisAccessTokenStore },
    AccessTokenMinter,
    BasicCredentialsExtractor,
    BearerGuard,
    { provide: APP_GUARD, useClass: BearerGuard },
  ],
  exports: [ACCESS_TOKEN_STORE, AccessTokenMinter, BearerGuard],
})
export class AuthModule {}
