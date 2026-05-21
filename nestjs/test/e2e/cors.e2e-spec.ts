import request from 'supertest';
import { bootTestApp, TestAppHandle } from '@test/setup/test-app';

describe('CORS (e2e)', () => {
  let handle: TestAppHandle;
  beforeAll(async () => { handle = await bootTestApp({ ALLOWED_ORIGIN: '.*' }); });
  afterAll(async () => { await handle.close(); });

  it('returns Access-Control-Allow-Origin and exposes x-cache', async () => {
    const res = await request(handle.app.getHttpServer())
      .options('/api/highlights')
      .set('Origin', 'http://localhost:3000')
      .set('Access-Control-Request-Method', 'GET')
      .set('Access-Control-Request-Headers', 'authorization');
    expect([200, 204]).toContain(res.status);
    expect(res.headers['access-control-allow-origin']).toBeTruthy();
    expect(res.headers['access-control-allow-methods']).toMatch(/GET/);
    expect((res.headers['access-control-expose-headers'] ?? '').toLowerCase()).toContain('x-cache');
  });
});
