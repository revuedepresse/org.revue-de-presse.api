export const LOGGER = Symbol('LOGGER');

export interface Logger {
  log(message: unknown, context?: string): void;
  error(message: unknown, trace?: string, context?: string): void;
  warn(message: unknown, context?: string): void;
  debug(message: unknown, context?: string): void;
}

export class NoopLogger implements Logger {
  log(): void {}
  error(): void {}
  warn(): void {}
  debug(): void {}
}

export class ConsoleLogger implements Logger {
  constructor(private readonly defaultContext?: string) {}
  private prefix(level: string, ctx?: string): string {
    return `[${level}][${ctx ?? this.defaultContext ?? 'app'}]`;
  }
  log(message: unknown, context?: string): void { console.log(this.prefix('log', context), message); }
  error(message: unknown, trace?: string, context?: string): void { console.error(this.prefix('error', context), message, trace ?? ''); }
  warn(message: unknown, context?: string): void { console.warn(this.prefix('warn', context), message); }
  debug(message: unknown, context?: string): void { console.debug(this.prefix('debug', context), message); }
}
