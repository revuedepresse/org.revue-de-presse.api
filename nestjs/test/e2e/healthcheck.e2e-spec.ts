import request from 'supertest';
import { bootTestApp, TestAppHandle } from '@test/setup/test-app';

describe('GET /api/healthcheck (e2e)', () => {
  let handle: TestAppHandle;
  beforeAll(async () => { handle = await bootTestApp(); });
  afterAll(async () => { await handle.close(); });

  it('returns empty array with Cache-Control: no-store', async () => {
    const res = await request(handle.app.getHttpServer()).get('/api/healthcheck');
    expect(res.status).toBe(200);
    expect(res.headers['cache-control']).toContain('no-store');
    expect(res.body).toEqual([]);
  });

  it('OPTIONS preflight returns 200', async () => {
    const res = await request(handle.app.getHttpServer())
      .options('/api/healthcheck')
      .set('Origin', 'http://localhost:3000')
      .set('Access-Control-Request-Method', 'GET');
    expect([200, 204]).toContain(res.status);
  });
});
