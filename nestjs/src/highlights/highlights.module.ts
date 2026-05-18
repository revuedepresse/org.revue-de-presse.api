import { Module } from '@nestjs/common';
import { ENV } from '@/config/env';
import type { Env } from '@/config/env';
import { RedisModule } from '@/redis/redis.module';
import { HighlightsController } from './highlights.controller';
import { HighlightsService } from './highlights.service';
import { HighlightFilters } from './highlight-filters';
import { HighlightNormalizer } from './highlight-normalizer';
import { SNAPSHOT_READER } from './snapshot-reader';
import { FilesystemSnapshotReader } from './filesystem-snapshot-reader';

@Module({
  imports: [RedisModule],
  controllers: [HighlightsController],
  providers: [
    HighlightsService,
    HighlightFilters,
    HighlightNormalizer,
    {
      provide: SNAPSHOT_READER,
      useFactory: (env: Env) => new FilesystemSnapshotReader(env),
      inject: [ENV],
    },
  ],
  exports: [HighlightsService],
})
export class HighlightsModule {}
