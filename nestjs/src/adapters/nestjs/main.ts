import 'reflect-metadata';
import { NestFactory } from '@nestjs/core';
import type { NestExpressApplication } from '@nestjs/platform-express';
import { AppModule } from './app.module';
import { loadEnv } from '@/config/env';
import { ProblemJsonFilter } from './common/problem-json.filter';
import { setupSwagger } from './common/swagger';
import { PinoLogger } from './common/pino-logger';

async function bootstrap() {
  const env = loadEnv(process.env);
  const app = await NestFactory.create<NestExpressApplication>(AppModule, {
    bufferLogs: true,
    logger: new PinoLogger(env.APP_ENV),
  });

  app.setGlobalPrefix('api');
  app.set('trust proxy', env.TRUSTED_PROXIES ?? false);

  app.enableCors({
    origin: new RegExp(env.ALLOWED_ORIGIN),
    methods: ['GET', 'POST', 'OPTIONS'],
    allowedHeaders: ['authorization', 'content-type', 'accept', 'x-benchmark', 'if-none-match'],
    exposedHeaders: [
      'x-cache', 'etag', 'age',
      'ratelimit-limit', 'ratelimit-remaining', 'ratelimit-reset', 'retry-after',
    ],
    credentials: false,
    maxAge: 3600,
  });

  app.useGlobalFilters(new ProblemJsonFilter());

  // Content negotiation for /api/highlights: Accept: application/ld+json wins.
  app.use((req: { headers: Record<string, string> }, _res: unknown, next: () => void) => {
    const accept = req.headers.accept ?? '';
    if (typeof accept === 'string' && accept.includes('application/ld+json')) {
      // Hint downstream — controller reads this directly.
      req.headers['accept'] = 'application/ld+json';
    }
    next();
  });

  setupSwagger(app);

  const port = env.PORT ?? 3000;
  await app.listen(port);
}

void bootstrap();
