import { CallHandler, ExecutionContext, Injectable, NestInterceptor } from '@nestjs/common';
import { Observable } from 'rxjs';
import { tap } from 'rxjs/operators';
import type { Response } from 'express';

@Injectable()
export class CacheLabelInterceptor implements NestInterceptor {
  intercept(ctx: ExecutionContext, next: CallHandler): Observable<unknown> {
    const req = ctx.switchToHttp().getRequest<{ _highlights_cache?: string }>();
    const res = ctx.switchToHttp().getResponse<Response>();
    return next.handle().pipe(
      tap(() => {
        const label = req._highlights_cache;
        if (typeof label === 'string') res.setHeader('x-cache', label);
      }),
    );
  }
}
