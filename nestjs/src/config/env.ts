import { z } from 'zod';

const boolish = z.preprocess(
  (v) => (typeof v === 'string' ? ['1', 'true', 'TRUE', 'yes'].includes(v) : v),
  z.boolean(),
);
const intish = z.preprocess((v) => (typeof v === 'string' ? Number(v) : v), z.number().int().positive());

export const EnvSchema = z.object({
  APP_ENV: z.enum(['dev', 'prod', 'test']),
  APP_SECRET: z.string().optional(),
  DATABASE_URL: z.string().min(1),
  REDIS_HOST: z.string().min(1),
  REDIS_PORT: intish,
  ALLOWED_ORIGIN: z.string().min(1),
  RATE_LIMIT_ENABLED: boolish,
  TRUSTED_PROXIES: z.string().optional(),
  PROJECT_DIR: z.string().optional(),
  PORT: intish.optional(),
  PG_POOL_MIN: intish.optional(),
  PG_POOL_MAX: intish.optional(),
  TIKTOK_CLIENT_KEY: z.string().optional(),
  TIKTOK_CLIENT_SECRET: z.string().optional(),
});

export type Env = z.infer<typeof EnvSchema>;

export function loadEnv(source: Record<string, string | undefined> = process.env): Env {
  const merged = { ...source };
  if (!merged.ALLOWED_ORIGIN && merged.CORS_ALLOW_ORIGIN) {
    merged.ALLOWED_ORIGIN = merged.CORS_ALLOW_ORIGIN;
  }
  const parsed = EnvSchema.safeParse(merged);
  if (!parsed.success) {
    const issues = parsed.error.issues.map((i) => `${i.path.join('.')}: ${i.message}`).join('; ');
    throw new Error(`Invalid environment: ${issues}`);
  }
  return parsed.data;
}

export const ENV = Symbol('ENV');
