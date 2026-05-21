import { Module } from '@nestjs/common';
import { ENV } from '@/config/env';
import type { Env } from '@/config/env';
import {
  HttpTikTokOAuthClient,
  TIKTOK_OAUTH_CLIENT,
  type TikTokOAuthClient,
} from '@/core/tiktok/tiktok-oauth.client';
import { TikTokExchangeError } from '@/core/tiktok/tiktok-oauth.errors';
import { TikTokOAuthController } from './tiktok-oauth.controller';

/**
 * The real client is constructed lazily — if env vars are absent we still
 * wire up a placeholder so DI resolves. The controller short-circuits to
 * 503 before calling into it.
 */
class UnconfiguredTikTokOAuthClient implements TikTokOAuthClient {
  exchangeAuthorizationCode(): Promise<never> {
    return Promise.reject(
      new TikTokExchangeError(503, {
        error: 'unconfigured',
        error_description: 'TikTok credentials not configured on the server',
      }),
    );
  }
}

@Module({
  controllers: [TikTokOAuthController],
  providers: [
    {
      provide: TIKTOK_OAUTH_CLIENT,
      useFactory: (env: Env): TikTokOAuthClient => {
        if (env.TIKTOK_CLIENT_KEY && env.TIKTOK_CLIENT_SECRET) {
          return new HttpTikTokOAuthClient(
            env.TIKTOK_CLIENT_KEY,
            env.TIKTOK_CLIENT_SECRET,
          );
        }
        return new UnconfiguredTikTokOAuthClient();
      },
      inject: [ENV],
    },
  ],
})
export class TikTokModule {}
