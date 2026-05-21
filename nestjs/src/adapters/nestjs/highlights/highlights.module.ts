import { Module } from '@nestjs/common';
import { ENV } from '@/config/env';
import type { Env } from '@/config/env';
import type Redis from 'ioredis';
import { REDIS_CLIENT } from '@/adapters/persistence/redis/redis.tokens';
import { RedisModule } from '@/adapters/persistence/redis/redis.module';
import { HighlightsController } from './highlights.controller';
import { HighlightsService } from '@/core/highlights/highlights.service';
import { HighlightFilters } from '@/core/highlights/highlight-filters';
import { HighlightNormalizer } from '@/core/highlights/highlight-normalizer';
import { SNAPSHOT_READER, SnapshotReader } from '@/core/highlights/snapshot-reader';
import { FilesystemSnapshotReader } from '@/adapters/snapshots/filesystem-snapshot-reader';

@Module({
  imports: [RedisModule],
  controllers: [HighlightsController],
  providers: [
    HighlightFilters,
    HighlightNormalizer,
    {
      provide: SNAPSHOT_READER,
      useFactory: (env: Env) => new FilesystemSnapshotReader(env),
      inject: [ENV],
    },
    {
      provide: HighlightsService,
      useFactory: (
        reader: SnapshotReader,
        filters: HighlightFilters,
        normalizer: HighlightNormalizer,
        redis: Redis,
        env: Env,
      ) => new HighlightsService(reader, filters, normalizer, redis, env),
      inject: [SNAPSHOT_READER, HighlightFilters, HighlightNormalizer, REDIS_CLIENT, ENV],
    },
  ],
  exports: [HighlightsService],
})
export class HighlightsModule {}
