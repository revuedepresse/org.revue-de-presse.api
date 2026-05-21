import { Global, Module } from '@nestjs/common';
import { ConfigModule } from './config/config.module';
import { DbModule } from '@/adapters/persistence/drizzle/db.module';
import { RedisModule } from '@/adapters/persistence/redis/redis.module';
import { AuthModule } from './auth/auth.module';
import { HighlightsModule } from './highlights/highlights.module';
import { HealthModule } from './health/health.module';
import { RateLimitModule } from './rate-limit/rate-limit.module';
import { TikTokModule } from './tiktok/tiktok.module';

@Global()
@Module({
  imports: [
    ConfigModule,
    DbModule,
    RedisModule,
    AuthModule,
    HighlightsModule,
    HealthModule,
    RateLimitModule,
    TikTokModule,
  ],
})
export class AppModule {}
