import { ArgumentsHost, Catch, ExceptionFilter, HttpException, HttpStatus } from '@nestjs/common';
import type { Response } from 'express';

@Catch()
export class ProblemJsonFilter implements ExceptionFilter {
  catch(exception: unknown, host: ArgumentsHost): void {
    const res = host.switchToHttp().getResponse<Response>();
    const isHttp = exception instanceof HttpException;
    const status = isHttp ? exception.getStatus() : HttpStatus.INTERNAL_SERVER_ERROR;
    const title = isHttp ? exception.message || 'Error' : 'Internal Server Error';
    const exposedTitle = status === 401 ? 'Unauthorized' : title;
    const detail = isHttp ? extractDetail(exception) : 'Unexpected error';

    res.status(status);
    res.setHeader('Content-Type', 'application/problem+json');
    res.json({
      type: 'about:blank',
      title: exposedTitle,
      status,
      detail,
    });
  }
}

function extractDetail(e: HttpException): string {
  const r = e.getResponse();
  if (typeof r === 'string') return r;
  if (r && typeof r === 'object') {
    const obj = r as Record<string, unknown>;
    if (typeof obj.detail === 'string') return obj.detail;
    if (typeof obj.message === 'string') return obj.message;
    if (Array.isArray(obj.message)) return obj.message.join('; ');
  }
  return e.message;
}
