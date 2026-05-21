import request from 'supertest';
import { bootTestApp, fixturesProjectDir, TestAppHandle } from '@test/setup/test-app';
import { seedMember } from '@test/setup/seed-member';

const basic = (s: string) => 'Basic ' + Buffer.from(s).toString('base64');
const SECRET = 'dummy-test-secret';

describe('GET /api/highlights (e2e)', () => {
  let handle: TestAppHandle;
  let bearer: string;

  beforeAll(async () => {
    handle = await bootTestApp({ PROJECT_DIR: fixturesProjectDir() });
    seedMember(handle.sqlite, SECRET);
    const mint = await request(handle.app.getHttpServer())
      .post('/api/token').set('Authorization', basic(':' + SECRET));
    bearer = mint.body.access_token;
  });
  afterAll(async () => { await handle.close(); });

  it('unauthenticated request returns 401', async () => {
    const res = await request(handle.app.getHttpServer())
      .get('/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0');
    expect(res.status).toBe(401);
  });

  it('authenticated request returns the Hydra collection envelope', async () => {
    const res = await request(handle.app.getHttpServer())
      .get('/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0')
      .set('Authorization', 'Bearer ' + bearer)
      .set('Accept', 'application/ld+json');
    expect(res.status).toBe(200);
    expect(res.body['@type']).toBe('Collection');
    expect(res.body).toHaveProperty('member');
    expect(res.body).toHaveProperty('totalItems');
    expect(Array.isArray(res.body.member)).toBe(true);
  });

  it('response has Cache-Control: max-age=3600', async () => {
    const res = await request(handle.app.getHttpServer())
      .get('/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0')
      .set('Authorization', 'Bearer ' + bearer);
    expect(res.headers['cache-control']).toContain('max-age=3600');
  });

  it('response has Vary header', async () => {
    const res = await request(handle.app.getHttpServer())
      .get('/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0')
      .set('Authorization', 'Bearer ' + bearer);
    expect(res.headers['vary']).toBeTruthy();
  });

  it('bogus bearer returns 401', async () => {
    const res = await request(handle.app.getHttpServer())
      .get('/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0')
      .set('Authorization', 'Bearer ' + 'a'.repeat(64));
    expect(res.status).toBe(401);
  });
});
