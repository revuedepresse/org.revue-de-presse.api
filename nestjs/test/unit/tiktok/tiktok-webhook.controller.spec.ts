import {
  ServiceUnavailableException,
  UnauthorizedException,
} from '@nestjs/common';
import type { RawBodyRequest } from '@nestjs/common';
import type { Request } from 'express';
import { TikTokWebhookController } from '@/adapters/nestjs/tiktok/tiktok-webhook.controller';
import {
  TikTokWebhookVerificationError,
  type TikTokWebhookVerifier,
  type TikTokWebhookVerifyInput,
} from '@/core/tiktok/tiktok-webhook.verifier';
import type { TikTokWebhookEnvelope } from '@/core/tiktok/tiktok-webhook.types';
import type { Env } from '@/config/env';

class FakeVerifier implements TikTokWebhookVerifier {
  public calls: TikTokWebhookVerifyInput[] = [];
  constructor(
    private readonly result: TikTokWebhookEnvelope | Error,
  ) {}
  verifyAndParse(input: TikTokWebhookVerifyInput): TikTokWebhookEnvelope {
    this.calls.push(input);
    if (this.result instanceof Error) {
      throw this.result;
    }
    return this.result;
  }
}

function mockReq(
  headers: Record<string, string | undefined>,
  body: string | undefined,
): RawBodyRequest<Request> {
  return {
    headers,
    rawBody: body !== undefined ? Buffer.from(body, 'utf8') : undefined,
  } as unknown as RawBodyRequest<Request>;
}

function makeEnv(overrides: Partial<Env> = {}): Env {
  return {
    APP_ENV: 'test',
    DATABASE_URL: 'memory',
    REDIS_HOST: 'redis',
    REDIS_PORT: 6379,
    ALLOWED_ORIGIN: '.*',
    RATE_LIMIT_ENABLED: false,
    TIKTOK_CLIENT_SECRET: 'secret',
    ...overrides,
  } as Env;
}

const VALID_ENVELOPE: TikTokWebhookEnvelope = {
  event: 'video.publish.complete',
  client_key: 'client_key_abc',
  create_time: 1_700_000_000,
  user_openid: 'openid-1',
};

describe('TikTokWebhookController (unit)', () => {
  it('delegates signature verification to the injected verifier and acks with { ok: true }', () => {
    const verifier = new FakeVerifier(VALID_ENVELOPE);
    const controller = new TikTokWebhookController(makeEnv(), verifier);

    const body = '{"event":"video.publish.complete","client_key":"client_key_abc"}';
    const req = mockReq({ 'tiktok-signature': 't=1700000000,s=deadbeef' }, body);

    const result = controller.callback(req);

    expect(result).toEqual({ ok: true });
    expect(verifier.calls).toEqual([
      { signatureHeader: 't=1700000000,s=deadbeef', rawBody: body },
    ]);
  });

  it('returns 503 when TIKTOK_CLIENT_SECRET is not configured (and never calls the verifier)', () => {
    const verifier = new FakeVerifier(VALID_ENVELOPE);
    const controller = new TikTokWebhookController(
      makeEnv({ TIKTOK_CLIENT_SECRET: undefined }),
      verifier,
    );

    expect(() =>
      controller.callback(mockReq({ 'tiktok-signature': 't=1,s=x' }, '{}')),
    ).toThrow(ServiceUnavailableException);
    expect(verifier.calls).toHaveLength(0);
  });

  it('maps TikTokWebhookVerificationError to 401 Unauthorized', () => {
    const verifier = new FakeVerifier(
      new TikTokWebhookVerificationError('signature mismatch'),
    );
    const controller = new TikTokWebhookController(makeEnv(), verifier);

    expect(() =>
      controller.callback(mockReq({ 'tiktok-signature': 't=1,s=bad' }, '{}')),
    ).toThrow(UnauthorizedException);
  });

  it('propagates a missing signature header through to the verifier as undefined', () => {
    const verifier = new FakeVerifier(
      new TikTokWebhookVerificationError('missing TikTok-Signature header'),
    );
    const controller = new TikTokWebhookController(makeEnv(), verifier);

    expect(() => controller.callback(mockReq({}, '{}'))).toThrow(
      UnauthorizedException,
    );
    expect(verifier.calls[0]).toEqual({
      signatureHeader: undefined,
      rawBody: '{}',
    });
  });

  it('passes an empty string rawBody when the request has no raw buffer', () => {
    const verifier = new FakeVerifier(VALID_ENVELOPE);
    const controller = new TikTokWebhookController(makeEnv(), verifier);

    const result = controller.callback(
      mockReq({ 'tiktok-signature': 't=1,s=x' }, undefined),
    );

    expect(result).toEqual({ ok: true });
    expect(verifier.calls[0]?.rawBody).toBe('');
  });

  it('rethrows unexpected (non-verification) errors from the verifier unchanged', () => {
    const boom = new Error('upstream blew up');
    const verifier: TikTokWebhookVerifier = {
      verifyAndParse: (): never => {
        throw boom;
      },
    };
    const controller = new TikTokWebhookController(makeEnv(), verifier);

    expect(() =>
      controller.callback(mockReq({ 'tiktok-signature': 't=1,s=x' }, '{}')),
    ).toThrow(boom);
  });
});
