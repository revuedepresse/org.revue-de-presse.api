import request from 'supertest';
import { bootTestApp, TestAppHandle } from '@test/setup/test-app';

describe('GET /api/tiktok/oauth/callback (e2e)', () => {
  let handle: TestAppHandle;
  beforeAll(async () => {
    handle = await bootTestApp();
  });
  afterAll(async () => {
    await handle.close();
  });

  it('renders an HTML page when code + state are present', async () => {
    const res = await request(handle.app.getHttpServer())
      .get('/api/tiktok/oauth/callback')
      .query({ code: 'auth-code-abc', state: 'state-xyz' });

    expect(res.status).toBe(200);
    expect(res.headers['content-type']).toMatch(/text\/html/);
    expect(res.headers['cache-control']).toContain('no-store');
    expect(res.text).toContain('auth-code-abc');
    expect(res.text).toContain('state-xyz');
    expect(res.text).toContain('Paste this URL back into the bootstrap CLI');
    expect(res.text).toContain('<textarea readonly');
  });

  it('html-escapes user-controlled values in code/state', async () => {
    const res = await request(handle.app.getHttpServer())
      .get('/api/tiktok/oauth/callback')
      .query({ code: '<script>alert(1)</script>', state: 'st"ate' });

    expect(res.status).toBe(200);
    expect(res.text).not.toContain('<script>alert(1)</script>');
    expect(res.text).toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
    expect(res.text).toContain('st&quot;ate');
  });

  it('returns 400 application/problem+json when code is missing', async () => {
    const res = await request(handle.app.getHttpServer())
      .get('/api/tiktok/oauth/callback')
      .query({ state: 'st' });

    expect(res.status).toBe(400);
    expect(res.headers['content-type']).toMatch(/application\/problem\+json/);
    expect(res.body.detail).toMatch(/code.*state/i);
  });

  it('returns 400 application/problem+json when state is missing', async () => {
    const res = await request(handle.app.getHttpServer())
      .get('/api/tiktok/oauth/callback')
      .query({ code: 'c' });

    expect(res.status).toBe(400);
    expect(res.headers['content-type']).toMatch(/application\/problem\+json/);
  });

  it('returns 400 application/problem+json when error is present', async () => {
    const res = await request(handle.app.getHttpServer())
      .get('/api/tiktok/oauth/callback')
      .query({ error: 'access_denied', error_description: 'user cancelled' });

    expect(res.status).toBe(400);
    expect(res.headers['content-type']).toMatch(/application\/problem\+json/);
    expect(res.body.detail).toContain('access_denied');
    expect(res.body.detail).toContain('user cancelled');
  });

  it('is publicly reachable without a Bearer token', async () => {
    const res = await request(handle.app.getHttpServer())
      .get('/api/tiktok/oauth/callback')
      .query({ code: 'c', state: 's' });
    expect(res.status).toBe(200);
  });
});
