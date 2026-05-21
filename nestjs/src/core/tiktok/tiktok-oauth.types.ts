import { z } from 'zod';

/**
 * Shape of a successful response from
 * `POST https://open.tiktokapis.com/v2/oauth/token/` with
 * `grant_type=authorization_code`.
 *
 * Fields not strictly used downstream are kept optional so a partial
 * TikTok response still validates instead of crashing the controller.
 */
export const TikTokTokenResponseSchema = z.object({
  access_token: z.string().min(1),
  refresh_token: z.string().min(1),
  expires_in: z.number().int().positive(),
  refresh_expires_in: z.number().int().positive().optional(),
  scope: z.string().optional(),
  open_id: z.string().optional(),
  token_type: z.string().optional(),
});

export type TikTokTokenResponse = z.infer<typeof TikTokTokenResponseSchema>;

/**
 * Body accepted by `POST /api/tiktok/oauth/exchange`. Hand-validated by zod
 * inside the controller — kept here so unit + e2e tests can re-use it.
 */
export const TikTokExchangeRequestSchema = z.object({
  code: z.string().min(1),
  code_verifier: z.string().min(1),
  redirect_uri: z.string().min(1),
});

export type TikTokExchangeRequest = z.infer<typeof TikTokExchangeRequestSchema>;
