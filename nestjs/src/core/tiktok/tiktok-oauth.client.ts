import {
  TikTokTokenResponse,
  TikTokTokenResponseSchema,
} from './tiktok-oauth.types';
import {
  TikTokExchangeError,
  TikTokInvalidResponseError,
} from './tiktok-oauth.errors';

/**
 * DI symbol for the TikTok OAuth client. Tests override this provider to
 * inject `FakeTikTokOAuthClient` instead of `HttpTikTokOAuthClient`.
 */
export const TIKTOK_OAUTH_CLIENT = Symbol('TIKTOK_OAUTH_CLIENT');

export interface TikTokOAuthExchangeInput {
  code: string;
  codeVerifier: string;
  redirectUri: string;
}

export interface TikTokOAuthClient {
  exchangeAuthorizationCode(
    input: TikTokOAuthExchangeInput,
  ): Promise<TikTokTokenResponse>;
}

const TIKTOK_TOKEN_ENDPOINT = 'https://open.tiktokapis.com/v2/oauth/token/';

/**
 * Production implementation. Posts a form-encoded body to the TikTok token
 * endpoint and validates the response with zod. Constructor injection lets us
 * supply a fake `fetch` from unit tests without polluting globals.
 */
export class HttpTikTokOAuthClient implements TikTokOAuthClient {
  constructor(
    private readonly clientKey: string,
    private readonly clientSecret: string,
    private readonly fetchImpl: typeof fetch = fetch,
    private readonly endpoint: string = TIKTOK_TOKEN_ENDPOINT,
  ) {}

  async exchangeAuthorizationCode(
    input: TikTokOAuthExchangeInput,
  ): Promise<TikTokTokenResponse> {
    const body = new URLSearchParams({
      client_key: this.clientKey,
      client_secret: this.clientSecret,
      code: input.code,
      grant_type: 'authorization_code',
      redirect_uri: input.redirectUri,
      code_verifier: input.codeVerifier,
    });

    const res = await this.fetchImpl(this.endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Cache-Control': 'no-cache',
      },
      body: body.toString(),
    });

    // TikTok occasionally returns 200 with an `error` field inside the JSON
    // body; we treat any non-2xx OR any body lacking `access_token` as a
    // failed exchange.
    const raw = await safeJson(res);

    if (!res.ok) {
      throw new TikTokExchangeError(res.status, raw);
    }

    const parsed = TikTokTokenResponseSchema.safeParse(raw);
    if (!parsed.success) {
      // If the upstream returned an `error` field (200-with-error pattern),
      // surface that as an exchange error rather than a parse error so the
      // controller can map it to a 400.
      if (raw && typeof raw === 'object' && 'error' in (raw as Record<string, unknown>)) {
        throw new TikTokExchangeError(400, raw);
      }
      const issues = parsed.error.issues
        .map((i) => `${i.path.join('.')}: ${i.message}`)
        .join('; ');
      throw new TikTokInvalidResponseError(issues, raw);
    }

    return parsed.data;
  }
}

async function safeJson(res: Response): Promise<unknown> {
  try {
    return await res.json();
  } catch {
    return null;
  }
}
