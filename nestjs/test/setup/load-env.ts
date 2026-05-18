import * as path from 'node:path';
import * as dotenvFlow from 'dotenv-flow';

dotenvFlow.config({ path: path.resolve(__dirname, '../../..'), node_env: 'test', silent: true });
if (!process.env.DATABASE_URL) process.env.DATABASE_URL = 'sqlite:///:memory:';
if (!process.env.APP_ENV) process.env.APP_ENV = 'test';
if (!process.env.REDIS_HOST) process.env.REDIS_HOST = '127.0.0.1';
if (!process.env.REDIS_PORT) process.env.REDIS_PORT = '6379';
if (!process.env.ALLOWED_ORIGIN) process.env.ALLOWED_ORIGIN = '.*';
if (!process.env.RATE_LIMIT_ENABLED) process.env.RATE_LIMIT_ENABLED = 'false';
