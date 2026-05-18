import { Module } from '@nestjs/common';
import { APP_GUARD } from '@nestjs/core';
import { RedisModule } from '@/redis/redis.module';
import { RedisRateLimiter } from './redis-rate-limiter';
import { RateLimitGuard } from './rate-limit.guard';

@Module({
  imports: [RedisModule],
  providers: [RedisRateLimiter, RateLimitGuard, { provide: APP_GUARD, useClass: RateLimitGuard }],
  exports: [RedisRateLimiter],
})
export class RateLimitModule {}
