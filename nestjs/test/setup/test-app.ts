import * as path from 'node:path';
import Database from 'better-sqlite3';
import { Test } from '@nestjs/testing';
import { NestExpressApplication } from '@nestjs/platform-express';
import { AppModule } from '@/app.module';
import { DB } from '@/db/db.tokens';
import { REDIS_CLIENT } from '@/redis/redis.tokens';
import { drizzle } from 'drizzle-orm/better-sqlite3';
import * as schema from '@/db/schema';
import { ProblemJsonFilter } from '@/common/problem-json.filter';
import { ENV, loadEnv } from '@/config/env';
import { InMemoryRedis } from '@test/doubles/in-memory-redis';
import { InMemoryRedisWithEval } from '@test/doubles/in-memory-redis-with-eval';
import { bootstrapSqlite } from './sqlite-bootstrap';

export interface TestAppHandle {
  app: NestExpressApplication;
  sqlite: Database.Database;
  redis: InMemoryRedis;
  close: () => Promise<void>;
}

export async function bootTestApp(
  overrides: Partial<Record<string, string>> & { useEvalRedis?: boolean } = {},
): Promise<TestAppHandle> {
  const { useEvalRedis, ...envOverrides } = overrides as Record<string, unknown>;
  for (const [k, v] of Object.entries(envOverrides)) {
    if (typeof v === 'string') process.env[k] = v;
  }
  // Ensure ALLOWED_ORIGIN is a valid JS regex string ('*' from .env.test is not valid).
  if (!overrides.ALLOWED_ORIGIN && (!process.env.ALLOWED_ORIGIN || process.env.ALLOWED_ORIGIN === '*')) {
    process.env.ALLOWED_ORIGIN = '.*';
  }
  const env = loadEnv(process.env);
  const sqlite = new Database(':memory:');
  bootstrapSqlite(sqlite);
  const db = drizzle(sqlite, { schema });
  const redis = useEvalRedis ? new InMemoryRedisWithEval() : new InMemoryRedis();

  const moduleRef = await Test.createTestingModule({ imports: [AppModule] })
    .overrideProvider(ENV).useValue(env)
    .overrideProvider(DB).useValue(db)
    .overrideProvider(REDIS_CLIENT).useValue(redis)
    .compile();

  const app = moduleRef.createNestApplication<NestExpressApplication>();
  app.setGlobalPrefix('api');
  app.useGlobalFilters(new ProblemJsonFilter());
  app.enableCors({
    origin: new RegExp(env.ALLOWED_ORIGIN),
    methods: ['GET', 'POST', 'OPTIONS'],
    allowedHeaders: ['authorization', 'content-type', 'accept', 'x-benchmark', 'if-none-match'],
    exposedHeaders: ['x-cache', 'etag', 'age', 'ratelimit-limit', 'ratelimit-remaining', 'ratelimit-reset', 'retry-after'],
    credentials: false,
    maxAge: 3600,
  });
  await app.init();
  return {
    app, sqlite, redis,
    close: async () => { await app.close(); sqlite.close(); },
  };
}

export function fixturesProjectDir(): string {
  return path.resolve(__dirname, '../fixtures-project');
}
