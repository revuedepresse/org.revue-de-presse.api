import { HttpStatus, UnauthorizedException } from '@nestjs/common';
import { ProblemJsonFilter } from '@/common/problem-json.filter';

function makeHost() {
  const headers: Record<string, string> = {};
  const res = {
    status: jest.fn().mockReturnThis(),
    setHeader: jest.fn((k: string, v: string) => { headers[k] = v; }),
    json: jest.fn(),
    headers,
  };
  const ctx = {
    switchToHttp: () => ({ getResponse: () => res, getRequest: () => ({ url: '/api/x' }) }),
  };
  return { ctx, res };
}

describe('ProblemJsonFilter', () => {
  it('maps UnauthorizedException to problem+json 401', () => {
    const filter = new ProblemJsonFilter();
    const { ctx, res } = makeHost();
    filter.catch(new UnauthorizedException('Invalid'), ctx as never);
    expect(res.status).toHaveBeenCalledWith(HttpStatus.UNAUTHORIZED);
    expect(res.setHeader).toHaveBeenCalledWith('Content-Type', 'application/problem+json');
    expect(res.json).toHaveBeenCalledWith({
      type: 'about:blank',
      title: 'Unauthorized',
      status: 401,
      detail: expect.any(String),
    });
  });
});
