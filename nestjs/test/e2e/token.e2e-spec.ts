import request from 'supertest';
import { bootTestApp, TestAppHandle } from '@test/setup/test-app';
import { seedMember } from '@test/setup/seed-member';

const basic = (s: string) => 'Basic ' + Buffer.from(s).toString('base64');
const SECRET = 'dummy-test-secret';

describe('POST /api/token (e2e)', () => {
  let handle: TestAppHandle;
  beforeAll(async () => {
    handle = await bootTestApp();
    seedMember(handle.sqlite, SECRET);
  });
  afterAll(async () => { await handle.close(); });

  it('Basic auth with valid secret returns Bearer', async () => {
    const res = await request(handle.app.getHttpServer())
      .post('/api/token').set('Authorization', basic(':' + SECRET));
    expect(res.status).toBe(201);
    expect(res.body.token_type).toBe('Bearer');
    expect(res.body.expires_in).toBe(900);
    expect(res.body.access_token).toMatch(/^[0-9a-f]{64}$/);
  });

  it('missing Authorization returns 401', async () => {
    const res = await request(handle.app.getHttpServer()).post('/api/token');
    expect(res.status).toBe(401);
  });

  it('invalid secret returns 401', async () => {
    const res = await request(handle.app.getHttpServer())
      .post('/api/token').set('Authorization', basic(':wrong'));
    expect(res.status).toBe(401);
  });

  it('malformed Basic header returns 401', async () => {
    const res = await request(handle.app.getHttpServer())
      .post('/api/token').set('Authorization', 'Basic !!!!');
    expect(res.status).toBe(401);
  });
});
