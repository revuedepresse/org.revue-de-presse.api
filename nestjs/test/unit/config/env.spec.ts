import { loadEnv } from '@/config/env';

describe('loadEnv', () => {
  const base = {
    APP_ENV: 'test',
    DATABASE_URL: 'sqlite:///:memory:',
    REDIS_HOST: '127.0.0.1',
    REDIS_PORT: '6379',
    ALLOWED_ORIGIN: '.*',
    RATE_LIMIT_ENABLED: 'false',
  };

  it('parses a minimal valid env', () => {
    const env = loadEnv(base);
    expect(env.APP_ENV).toBe('test');
    expect(env.REDIS_PORT).toBe(6379);
    expect(env.RATE_LIMIT_ENABLED).toBe(false);
  });

  it('rejects missing DATABASE_URL', () => {
    const { DATABASE_URL: _omit, ...without } = base;
    expect(() => loadEnv(without)).toThrow(/DATABASE_URL/);
  });

  it('rejects non-numeric REDIS_PORT', () => {
    expect(() => loadEnv({ ...base, REDIS_PORT: 'abc' })).toThrow(/REDIS_PORT/);
  });

  it('rejects unknown APP_ENV', () => {
    expect(() => loadEnv({ ...base, APP_ENV: 'staging' })).toThrow(/APP_ENV/);
  });

  it('accepts CORS_ALLOW_ORIGIN as a fallback when ALLOWED_ORIGIN is unset', () => {
    const { ALLOWED_ORIGIN: _o, ...without } = base;
    const env = loadEnv({ ...without, CORS_ALLOW_ORIGIN: '^https?://x$' });
    expect(env.ALLOWED_ORIGIN).toBe('^https?://x$');
  });
});
