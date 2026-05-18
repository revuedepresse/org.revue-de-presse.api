import { Module, OnApplicationShutdown, Inject } from '@nestjs/common';
import Redis from 'ioredis';
import { REDIS_CLIENT } from './redis.tokens';
import { ENV } from '@/config/env';
import type { Env } from '@/config/env';

@Module({
  providers: [
    {
      provide: REDIS_CLIENT,
      useFactory: (env: Env): Redis =>
        new Redis({ host: env.REDIS_HOST, port: env.REDIS_PORT, lazyConnect: false, maxRetriesPerRequest: 1 }),
      inject: [ENV],
    },
  ],
  exports: [REDIS_CLIENT],
})
export class RedisModule implements OnApplicationShutdown {
  constructor(@Inject(REDIS_CLIENT) private readonly client: Redis) {}
  async onApplicationShutdown(): Promise<void> {
    await this.client.quit().catch(() => undefined);
  }
}
