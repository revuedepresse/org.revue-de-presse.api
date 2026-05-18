import { LoggerService } from '@nestjs/common';
import pino, { Logger } from 'pino';

export class JsonLogger implements LoggerService {
  private readonly logger: Logger;
  constructor(appEnv: string) {
    this.logger = pino({
      level: appEnv === 'prod' ? 'info' : 'debug',
      transport: appEnv === 'prod' ? undefined : { target: 'pino-pretty' },
      base: { app: 'revue-de-presse-api-nest' },
    });
  }
  log(message: unknown, context?: string) { this.logger.info({ context }, String(message)); }
  error(message: unknown, trace?: string, context?: string) { this.logger.error({ context, trace }, String(message)); }
  warn(message: unknown, context?: string) { this.logger.warn({ context }, String(message)); }
  debug(message: unknown, context?: string) { this.logger.debug({ context }, String(message)); }
  verbose(message: unknown, context?: string) { this.logger.trace({ context }, String(message)); }
}
