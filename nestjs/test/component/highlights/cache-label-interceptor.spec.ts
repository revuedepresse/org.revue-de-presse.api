import { of } from 'rxjs';
import { CacheLabelInterceptor } from '@/common/cache-label.interceptor';
import type { CallHandler, ExecutionContext } from '@nestjs/common';

function makeCtx(reqAttr: string | null) {
  const headers: Record<string, string> = {};
  const req: { _highlights_cache?: string } = {};
  if (reqAttr !== null) req._highlights_cache = reqAttr;
  const res = { setHeader: (k: string, v: string) => { headers[k] = v; } };
  return {
    ctx: { switchToHttp: () => ({ getRequest: () => req, getResponse: () => res }) } as unknown as ExecutionContext,
    headers,
  };
}

describe('CacheLabelInterceptor', () => {
  it('copies _highlights_cache attribute to x-cache header', (done) => {
    const interceptor = new CacheLabelInterceptor();
    const { ctx, headers } = makeCtx('hit');
    const handler = { handle: () => of({}) } as CallHandler;
    interceptor.intercept(ctx, handler).subscribe(() => {
      expect(headers['x-cache']).toBe('hit');
      done();
    });
  });

  it('does not set header when attribute is absent', (done) => {
    const interceptor = new CacheLabelInterceptor();
    const { ctx, headers } = makeCtx(null);
    const handler = { handle: () => of({}) } as CallHandler;
    interceptor.intercept(ctx, handler).subscribe(() => {
      expect(headers['x-cache']).toBeUndefined();
      done();
    });
  });
});
