import { createHmac, timingSafeEqual } from 'node:crypto';
import {
  TikTokWebhookEnvelope,
  TikTokWebhookEnvelopeSchema,
} from './tiktok-webhook.types';

/**
 * DI symbol for the TikTok webhook signature verifier. Tests override this
 * provider with a fake verifier so they do not have to compute real HMACs.
 */
export const TIKTOK_WEBHOOK_VERIFIER = Symbol('TIKTOK_WEBHOOK_VERIFIER');

export class TikTokWebhookVerificationError extends Error {
  constructor(public readonly reason: string) {
    super(`TikTok webhook verification failed: ${reason}`);
    this.name = 'TikTokWebhookVerificationError';
  }
}

export interface TikTokWebhookVerifyInput {
  signatureHeader: string | undefined;
  rawBody: string;
}

export interface TikTokWebhookVerifier {
  verifyAndParse(input: TikTokWebhookVerifyInput): TikTokWebhookEnvelope;
}

/**
 * Production verifier. TikTok signs each webhook delivery with a
 * `TikTok-Signature` header in the format `t=<unix-seconds>,s=<hex>` where
 * the hex is HMAC-SHA256 of `"{t}.{raw-body}"` keyed by the app's
 * client_secret. We recompute the MAC and compare in constant time, and
 * reject signatures whose timestamp is more than MAX_AGE_SECONDS old to
 * bound the replay window.
 */
export class HmacTikTokWebhookVerifier implements TikTokWebhookVerifier {
  static readonly MAX_AGE_SECONDS = 5 * 60;

  constructor(
    private readonly clientSecret: string,
    private readonly now: () => number = () => Math.floor(Date.now() / 1000),
  ) {}

  verifyAndParse({
    signatureHeader,
    rawBody,
  }: TikTokWebhookVerifyInput): TikTokWebhookEnvelope {
    if (!signatureHeader) {
      throw new TikTokWebhookVerificationError(
        'missing TikTok-Signature header',
      );
    }

    const parts: Record<string, string> = {};
    for (const segment of signatureHeader.split(',')) {
      const eq = segment.indexOf('=');
      if (eq < 0) continue;
      parts[segment.slice(0, eq).trim()] = segment.slice(eq + 1).trim();
    }
    const t = parts.t;
    const s = parts.s;
    if (!t || !s) {
      throw new TikTokWebhookVerificationError(
        'malformed TikTok-Signature header',
      );
    }
    const timestamp = Number(t);
    if (!Number.isFinite(timestamp)) {
      throw new TikTokWebhookVerificationError(
        'non-numeric signature timestamp',
      );
    }
    if (
      Math.abs(this.now() - timestamp) >
      HmacTikTokWebhookVerifier.MAX_AGE_SECONDS
    ) {
      throw new TikTokWebhookVerificationError(
        'signature timestamp out of window',
      );
    }

    const expected = createHmac('sha256', this.clientSecret)
      .update(`${t}.${rawBody}`)
      .digest('hex');
    const a = Buffer.from(expected, 'hex');
    let b: Buffer;
    try {
      b = Buffer.from(s, 'hex');
    } catch {
      throw new TikTokWebhookVerificationError('non-hex signature');
    }
    if (a.length === 0 || a.length !== b.length || !timingSafeEqual(a, b)) {
      throw new TikTokWebhookVerificationError('signature mismatch');
    }

    let payload: unknown;
    try {
      payload = JSON.parse(rawBody);
    } catch {
      throw new TikTokWebhookVerificationError('body is not valid JSON');
    }
    const parsed = TikTokWebhookEnvelopeSchema.safeParse(payload);
    if (!parsed.success) {
      const issues = parsed.error.issues
        .map((i) => `${i.path.join('.')}: ${i.message}`)
        .join('; ');
      throw new TikTokWebhookVerificationError(
        `malformed event envelope: ${issues}`,
      );
    }
    return parsed.data;
  }
}
