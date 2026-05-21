import request from 'supertest';
import { bootTestApp, TestAppHandle } from '@test/setup/test-app';

describe('OpenAPI docs (e2e)', () => {
  let handle: TestAppHandle;
  beforeAll(async () => { handle = await bootTestApp(); });
  afterAll(async () => { await handle.close(); });

  it('GET /api/docs.json returns the OpenAPI document', async () => {
    const res = await request(handle.app.getHttpServer()).get('/api/docs.json');
    expect(res.status).toBe(200);
    expect(res.body.openapi).toMatch(/^3\./);
    expect(res.body.info.title).toBe('Revue de presse — HTTP API');
    expect(res.body.paths['/api/token']).toBeDefined();
    expect(res.body.paths['/api/highlights']).toBeDefined();
  });

  it('GET /api/docs.jsonld wraps the OpenAPI document', async () => {
    const res = await request(handle.app.getHttpServer()).get('/api/docs.jsonld');
    expect(res.status).toBe(200);
    expect(res.body['@type']).toBe('Documentation');
    expect(res.body.openapi).toBeDefined();
  });
});
