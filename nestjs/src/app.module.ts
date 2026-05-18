import { Global, Module } from '@nestjs/common';
import { ConfigModule } from './config/config.module';
import { DbModule } from './db/db.module';
import { RedisModule } from './redis/redis.module';
import { MembersModule } from './members/members.module';
import { AuthModule } from './auth/auth.module';
import { HighlightsModule } from './highlights/highlights.module';
import { HealthModule } from './health/health.module';
import { RateLimitModule } from './rate-limit/rate-limit.module';

@Global()
@Module({
  imports: [
    ConfigModule,
    DbModule,
    RedisModule,
    MembersModule,
    AuthModule,
    HighlightsModule,
    HealthModule,
    RateLimitModule,
  ],
})
export class AppModule {}
