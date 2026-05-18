import request from 'supertest';
import { bootTestApp, TestAppHandle } from '@test/setup/test-app';

const basic = (s: string) => 'Basic ' + Buffer.from(s).toString('base64');

describe('Rate limit on POST /api/token (e2e)', () => {
  let handle: TestAppHandle;
  beforeAll(async () => { handle = await bootTestApp({ RATE_LIMIT_ENABLED: 'true', useEvalRedis: true } as never); });
  afterAll(async () => { await handle.close(); });

  it('returns 429 after burst exceeds 3', async () => {
    const server = handle.app.getHttpServer();
    for (let i = 0; i < 3; i++) {
      const r = await request(server).post('/api/token').set('Authorization', basic(':wrong'));
      expect(r.status).toBe(401);
    }
    const r = await request(server).post('/api/token').set('Authorization', basic(':wrong'));
    expect(r.status).toBe(429);
    expect(r.headers['retry-after']).toBeTruthy();
    expect(r.headers['content-type']).toContain('application/problem+json');
  });
});
