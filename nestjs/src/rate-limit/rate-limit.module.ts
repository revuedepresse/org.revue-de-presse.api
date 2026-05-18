import { Module } from '@nestjs/common';
import { APP_GUARD } from '@nestjs/core';
import type Redis from 'ioredis';
import { RedisModule } from '@/adapters/persistence/redis/redis.module';
import { REDIS_CLIENT } from '@/adapters/persistence/redis/redis.tokens';
import { RedisRateLimiter } from '@/adapters/persistence/redis/redis-rate-limiter';
import { RATE_LIMITER } from '@/core/rate-limit/rate-limiter';
import { RateLimitGuard } from './rate-limit.guard';

@Module({
  imports: [RedisModule],
  providers: [
    {
      provide: RATE_LIMITER,
      useFactory: (redis: Redis) => new RedisRateLimiter(redis),
      inject: [REDIS_CLIENT],
    },
    RateLimitGuard,
    { provide: APP_GUARD, useClass: RateLimitGuard },
  ],
  exports: [RATE_LIMITER],
})
export class RateLimitModule {}
