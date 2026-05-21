import request from 'supertest';
import Database from 'better-sqlite3';
import { Test } from '@nestjs/testing';
import { NestExpressApplication } from '@nestjs/platform-express';
import { drizzle } from 'drizzle-orm/better-sqlite3';
import { AppModule } from '@/adapters/nestjs/app.module';
import { DB } from '@/adapters/persistence/drizzle/db.tokens';
import { REDIS_CLIENT } from '@/adapters/persistence/redis/redis.tokens';
import { ProblemJsonFilter } from '@/adapters/nestjs/common/problem-json.filter';
import * as schema from '@/adapters/persistence/drizzle/schema';
import { ENV, loadEnv } from '@/config/env';
import { TIKTOK_OAUTH_CLIENT } from '@/core/tiktok/tiktok-oauth.client';
import { InMemoryRedis } from '@test/doubles/in-memory-redis';
import { FakeTikTokOAuthClient } from '@test/doubles/fake-tiktok-oauth.client';
import { bootstrapSqlite } from '@test/setup/sqlite-bootstrap';
import { seedMember } from '@test/setup/seed-member';

const basic = (s: string) => 'Basic ' + Buffer.from(s).toString('base64');
const SECRET = 'tiktok-exchange-secret';

interface Handle {
  app: NestExpressApplication;
  sqlite: Database.Database;
  fake: FakeTikTokOAuthClient;
  close: () => Promise<void>;
}

/**
 * Custom boot mirroring `bootTestApp` so we can override the TIKTOK_OAUTH_CLIENT
 * provider with `FakeTikTokOAuthClient`. Kept here (rather than in
 * test/setup/test-app.ts) because the override is unique to this spec.
 */
async function bootWithFakeTikTok(envOverrides: Record<string, string | undefined>): Promise<Handle> {
  for (const [k, v] of Object.entries(envOverrides)) {
    if (v === undefined) delete process.env[k];
    else process.env[k] = v;
  }
  if (!process.env.ALLOWED_ORIGIN || process.env.ALLOWED_ORIGIN === '*') {
    process.env.ALLOWED_ORIGIN = '.*';
  }

  const env = loadEnv(process.env);
  const sqlite = new Database(':memory:');
  bootstrapSqlite(sqlite);
  const db = drizzle(sqlite, { schema });
  const redis = new InMemoryRedis();
  const fake = new FakeTikTokOAuthClient();

  const moduleRef = await Test.createTestingModule({ imports: [AppModule] })
    .overrideProvider(ENV).useValue(env)
    .overrideProvider(DB).useValue(db)
    .overrideProvider(REDIS_CLIENT).useValue(redis)
    .overrideProvider(TIKTOK_OAUTH_CLIENT).useValue(fake)
    .compile();

  const app = moduleRef.createNestApplication<NestExpressApplication>();
  app.setGlobalPrefix('api');
  app.useGlobalFilters(new ProblemJsonFilter());
  await app.init();
  return {
    app,
    sqlite,
    fake,
    close: async () => {
      await app.close();
      sqlite.close();
    },
  };
}

async function mintBearer(handle: Handle): Promise<string> {
  const res = await request(handle.app.getHttpServer())
    .post('/api/token')
    .set('Authorization', basic(':' + SECRET));
  if (res.status !== 201) {
    throw new Error(`token mint failed: ${res.status} ${JSON.stringify(res.body)}`);
  }
  return res.body.access_token as string;
}

describe('POST /api/tiktok/oauth/exchange (e2e)', () => {
  describe('with TikTok credentials configured', () => {
    let handle: Handle;
    let bearer: string;

    beforeAll(async () => {
      handle = await bootWithFakeTikTok({
        TIKTOK_CLIENT_KEY: 'test-key',
        TIKTOK_CLIENT_SECRET: 'test-secret',
      });
      seedMember(handle.sqlite, SECRET);
      bearer = await mintBearer(handle);
    });
    afterAll(async () => {
      await handle.close();
    });

    it('returns 200 with the TikTok token response on the happy path', async () => {
      handle.fake.setSuccess({
        access_token: 'tt-access',
        refresh_token: 'tt-refresh',
        expires_in: 86400,
        refresh_expires_in: 31536000,
        scope: 'video.upload',
        open_id: 'open_42',
      });

      const res = await request(handle.app.getHttpServer())
        .post('/api/tiktok/oauth/exchange')
        .set('Authorization', `Bearer ${bearer}`)
        .send({
          code: 'auth-code-1',
          code_verifier: 'verifier-1',
          redirect_uri: 'https://api.revue-de-presse.org/api/tiktok/oauth/callback',
        });

      expect(res.status).toBe(200);
      expect(res.body).toMatchObject({
        access_token: 'tt-access',
        refresh_token: 'tt-refresh',
        expires_in: 86400,
        open_id: 'open_42',
      });
      expect(handle.fake.lastInput).toEqual({
        code: 'auth-code-1',
        codeVerifier: 'verifier-1',
        redirectUri: 'https://api.revue-de-presse.org/api/tiktok/oauth/callback',
      });
    });

    it('returns 400 application/problem+json when upstream returns 4xx', async () => {
      handle.fake.setUpstreamFailure(400, {
        error: 'invalid_grant',
        error_description: 'Authorization code expired.',
      });

      const res = await request(handle.app.getHttpServer())
        .post('/api/tiktok/oauth/exchange')
        .set('Authorization', `Bearer ${bearer}`)
        .send({ code: 'c', code_verifier: 'v', redirect_uri: 'https://x.test/cb' });

      expect(res.status).toBe(400);
      expect(res.headers['content-type']).toMatch(/application\/problem\+json/);
      expect(res.body.detail).toContain('invalid_grant');
    });

    it('returns 400 when the request body is malformed', async () => {
      const res = await request(handle.app.getHttpServer())
        .post('/api/tiktok/oauth/exchange')
        .set('Authorization', `Bearer ${bearer}`)
        .send({ code: 'c' });

      expect(res.status).toBe(400);
      expect(res.headers['content-type']).toMatch(/application\/problem\+json/);
    });

    it('returns 401 when no Bearer token is provided', async () => {
      const res = await request(handle.app.getHttpServer())
        .post('/api/tiktok/oauth/exchange')
        .send({ code: 'c', code_verifier: 'v', redirect_uri: 'https://x.test/cb' });

      expect(res.status).toBe(401);
    });
  });

  describe('with TikTok credentials missing', () => {
    let handle: Handle;
    let bearer: string;

    beforeAll(async () => {
      handle = await bootWithFakeTikTok({
        TIKTOK_CLIENT_KEY: undefined,
        TIKTOK_CLIENT_SECRET: undefined,
      });
      seedMember(handle.sqlite, SECRET);
      bearer = await mintBearer(handle);
    });
    afterAll(async () => {
      await handle.close();
    });

    it('returns 503 application/problem+json with a clear detail', async () => {
      const res = await request(handle.app.getHttpServer())
        .post('/api/tiktok/oauth/exchange')
        .set('Authorization', `Bearer ${bearer}`)
        .send({ code: 'c', code_verifier: 'v', redirect_uri: 'https://x.test/cb' });

      expect(res.status).toBe(503);
      expect(res.headers['content-type']).toMatch(/application\/problem\+json/);
      expect(res.body.detail).toBe('TikTok credentials not configured on the server');
    });
  });
});
