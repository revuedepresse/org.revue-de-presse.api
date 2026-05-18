import { Module } from '@nestjs/common';
import { APP_GUARD } from '@nestjs/core';
import type Redis from 'ioredis';
import { RedisModule } from '@/redis/redis.module';
import { REDIS_CLIENT } from '@/redis/redis.tokens';
import { RedisRateLimiter } from './redis-rate-limiter';
import { RateLimitGuard } from './rate-limit.guard';

@Module({
  imports: [RedisModule],
  providers: [
    {
      provide: RedisRateLimiter,
      useFactory: (redis: Redis) => new RedisRateLimiter(redis),
      inject: [REDIS_CLIENT],
    },
    RateLimitGuard,
    { provide: APP_GUARD, useClass: RateLimitGuard },
  ],
  exports: [RedisRateLimiter],
})
export class RateLimitModule {}
