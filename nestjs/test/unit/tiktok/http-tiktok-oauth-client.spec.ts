import { HttpTikTokOAuthClient } from '@/core/tiktok/tiktok-oauth.client';
import {
  TikTokExchangeError,
  TikTokInvalidResponseError,
} from '@/core/tiktok/tiktok-oauth.errors';

type FetchArgs = { url: string; init: RequestInit | undefined };

function makeFetch(
  responder: () => { status: number; body: unknown },
): { fetch: typeof fetch; calls: FetchArgs[] } {
  const calls: FetchArgs[] = [];
  const fakeFetch = async (
    input: Parameters<typeof fetch>[0],
    init?: RequestInit,
  ): Promise<Response> => {
    calls.push({ url: String(input), init });
    const { status, body } = responder();
    return new Response(JSON.stringify(body), {
      status,
      headers: { 'Content-Type': 'application/json' },
    });
  };
  return { fetch: fakeFetch as typeof fetch, calls };
}

describe('HttpTikTokOAuthClient', () => {
  const validResponse = {
    access_token: 'tt-access-xyz',
    refresh_token: 'tt-refresh-xyz',
    expires_in: 86400,
    refresh_expires_in: 31536000,
    scope: 'video.upload',
    open_id: 'open_abc',
  };

  it('posts a form-encoded body to the TikTok token endpoint and returns the parsed response', async () => {
    const { fetch: fakeFetch, calls } = makeFetch(() => ({ status: 200, body: validResponse }));
    const client = new HttpTikTokOAuthClient('key123', 'sec456', fakeFetch);

    const out = await client.exchangeAuthorizationCode({
      code: 'auth-code-1',
      codeVerifier: 'verifier-1',
      redirectUri: 'https://api.revue-de-presse.org/api/tiktok/oauth/callback',
    });

    expect(out).toEqual(validResponse);
    expect(calls).toHaveLength(1);
    const [{ url, init }] = calls;
    expect(url).toBe('https://open.tiktokapis.com/v2/oauth/token/');
    expect(init?.method).toBe('POST');
    const headers = init?.headers as Record<string, string>;
    expect(headers['Content-Type']).toBe('application/x-www-form-urlencoded');
    const body = new URLSearchParams(init?.body as string);
    expect(body.get('client_key')).toBe('key123');
    expect(body.get('client_secret')).toBe('sec456');
    expect(body.get('grant_type')).toBe('authorization_code');
    expect(body.get('code')).toBe('auth-code-1');
    expect(body.get('code_verifier')).toBe('verifier-1');
    expect(body.get('redirect_uri')).toBe(
      'https://api.revue-de-presse.org/api/tiktok/oauth/callback',
    );
  });

  it('throws TikTokExchangeError when upstream returns 4xx with an error body', async () => {
    const { fetch: fakeFetch } = makeFetch(() => ({
      status: 400,
      body: { error: 'invalid_grant', error_description: 'Authorization code expired.' },
    }));
    const client = new HttpTikTokOAuthClient('k', 's', fakeFetch);

    await expect(
      client.exchangeAuthorizationCode({
        code: 'expired',
        codeVerifier: 'v',
        redirectUri: 'https://example.test/cb',
      }),
    ).rejects.toBeInstanceOf(TikTokExchangeError);
  });

  it('throws TikTokExchangeError when upstream returns 200 with an error field instead of tokens', async () => {
    const { fetch: fakeFetch } = makeFetch(() => ({
      status: 200,
      body: { error: 'invalid_request', error_description: 'Missing code_verifier' },
    }));
    const client = new HttpTikTokOAuthClient('k', 's', fakeFetch);

    await expect(
      client.exchangeAuthorizationCode({
        code: 'c',
        codeVerifier: 'v',
        redirectUri: 'https://example.test/cb',
      }),
    ).rejects.toBeInstanceOf(TikTokExchangeError);
  });

  it('throws TikTokInvalidResponseError when upstream returns a 2xx body that does not match the schema', async () => {
    const { fetch: fakeFetch } = makeFetch(() => ({
      status: 200,
      body: { something: 'unexpected' },
    }));
    const client = new HttpTikTokOAuthClient('k', 's', fakeFetch);

    await expect(
      client.exchangeAuthorizationCode({
        code: 'c',
        codeVerifier: 'v',
        redirectUri: 'https://example.test/cb',
      }),
    ).rejects.toBeInstanceOf(TikTokInvalidResponseError);
  });
});
