import { ApiProperty } from '@nestjs/swagger';

/**
 * Body schema for `POST /api/tiktok/oauth/exchange`. Runtime validation is
 * performed in the controller against
 * `TikTokExchangeRequestSchema` (zod). This DTO exists for Swagger only.
 */
export class TikTokOAuthExchangeDto {
  @ApiProperty({ description: 'TikTok-issued authorization code from the callback redirect.' })
  code!: string;

  @ApiProperty({ description: 'PKCE code_verifier matching the code_challenge used to start the flow.' })
  code_verifier!: string;

  @ApiProperty({ description: 'Redirect URI registered in the TikTok developer portal.' })
  redirect_uri!: string;
}
