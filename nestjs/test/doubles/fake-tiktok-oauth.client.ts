import {
  TikTokOAuthClient,
  TikTokOAuthExchangeInput,
} from '@/core/tiktok/tiktok-oauth.client';
import { TikTokTokenResponse } from '@/core/tiktok/tiktok-oauth.types';
import { TikTokExchangeError } from '@/core/tiktok/tiktok-oauth.errors';

/**
 * In-memory fake. Tests call `setSuccess()` or `setFailure()` before exercising
 * the controller; the controller then resolves this implementation via the
 * `TIKTOK_OAUTH_CLIENT` provider override.
 */
export class FakeTikTokOAuthClient implements TikTokOAuthClient {
  public lastInput: TikTokOAuthExchangeInput | null = null;
  private nextResponse: TikTokTokenResponse | null = null;
  private nextError: Error | null = null;

  setSuccess(response: TikTokTokenResponse): void {
    this.nextResponse = response;
    this.nextError = null;
  }

  setUpstreamFailure(status: number, body: unknown): void {
    this.nextError = new TikTokExchangeError(status, body);
    this.nextResponse = null;
  }

  setError(err: Error): void {
    this.nextError = err;
    this.nextResponse = null;
  }

  async exchangeAuthorizationCode(
    input: TikTokOAuthExchangeInput,
  ): Promise<TikTokTokenResponse> {
    this.lastInput = input;
    if (this.nextError) throw this.nextError;
    if (this.nextResponse) return this.nextResponse;
    throw new Error('FakeTikTokOAuthClient: no response configured');
  }
}
