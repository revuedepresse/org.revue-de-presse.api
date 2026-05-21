import type Redis from 'ioredis';
import type { Env } from './config/env';
import { type Logger, NoopLogger } from './core/ports/logger';

import { type AccessTokenStore } from './core/auth/access-token.store';
import { AccessTokenMinter } from './core/auth/access-token.minter';
import { BasicCredentialsExtractor } from './core/auth/basic-credentials.extractor';
import { type MembersRepository } from './core/members/members.repository';
import { HighlightFilters } from './core/highlights/highlight-filters';
import { HighlightNormalizer } from './core/highlights/highlight-normalizer';
import { HighlightsService } from './core/highlights/highlights.service';
import { type SnapshotReader } from './core/highlights/snapshot-reader';
import { type RateLimiter } from './core/rate-limit/rate-limiter';

import { RedisAccessTokenStore } from './adapters/persistence/redis/redis-access-token.store';
import { RedisRateLimiter } from './adapters/persistence/redis/redis-rate-limiter';
import { DrizzleMembersRepository } from './adapters/persistence/drizzle/drizzle-members.repository';
import { FilesystemSnapshotReader } from './adapters/snapshots/filesystem-snapshot-reader';
import type { Db } from './adapters/persistence/drizzle/db.module';

export interface AppServices {
  tokenStore: AccessTokenStore;
  minter: AccessTokenMinter;
  extractor: BasicCredentialsExtractor;
  members: MembersRepository;
  filters: HighlightFilters;
  normalizer: HighlightNormalizer;
  reader: SnapshotReader;
  highlights: HighlightsService;
  rateLimiter: RateLimiter;
}

export interface AppDeps {
  env: Env;
  redis: Redis;
  db: Db;
  logger?: Logger;
}

/**
 * Framework-agnostic composition root. Builds the entire service graph from
 * three external dependencies (env, redis, db) plus an optional logger. Any
 * HTTP adapter (NestJS, Fastify, Hono, ...) can build its routes on top of
 * the returned `AppServices`. The NestJS adapter wires the same providers
 * via `useFactory` per-module, but tests and future adapters can use this
 * function directly.
 */
export function composeApp(deps: AppDeps): AppServices {
  const logger = deps.logger ?? new NoopLogger();
  const members = new DrizzleMembersRepository(deps.db);
  const tokenStore = new RedisAccessTokenStore(deps.redis);
  const minter = new AccessTokenMinter(tokenStore, 900);
  const extractor = new BasicCredentialsExtractor(members);
  const filters = new HighlightFilters();
  const normalizer = new HighlightNormalizer();
  const reader = new FilesystemSnapshotReader(deps.env, logger);
  const highlights = new HighlightsService(reader, filters, normalizer, deps.redis, deps.env, logger);
  const rateLimiter = new RedisRateLimiter(deps.redis);
  return { tokenStore, minter, extractor, members, filters, normalizer, reader, highlights, rateLimiter };
}
