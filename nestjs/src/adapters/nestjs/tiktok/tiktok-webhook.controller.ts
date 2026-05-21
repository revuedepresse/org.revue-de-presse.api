import {
  Controller,
  HttpCode,
  HttpStatus,
  Inject,
  Logger,
  Post,
  Req,
  ServiceUnavailableException,
  UnauthorizedException,
} from '@nestjs/common';
import type { RawBodyRequest } from '@nestjs/common';
import { ApiOperation, ApiTags } from '@nestjs/swagger';
import type { Request } from 'express';
import { Public } from '@/adapters/nestjs/auth/public.decorator';
import { ENV } from '@/config/env';
import type { Env } from '@/config/env';
import {
  TIKTOK_WEBHOOK_VERIFIER,
  TikTokWebhookVerificationError,
  type TikTokWebhookVerifier,
} from '@/core/tiktok/tiktok-webhook.verifier';

@ApiTags('tiktok')
@Controller('tiktok/webhook')
export class TikTokWebhookController {
  private readonly logger = new Logger(TikTokWebhookController.name);

  constructor(
    @Inject(ENV) private readonly env: Env,
    @Inject(TIKTOK_WEBHOOK_VERIFIER)
    private readonly verifier: TikTokWebhookVerifier,
  ) {}

  @Public()
  @Post('callback')
  @HttpCode(HttpStatus.OK)
  @ApiOperation({
    summary:
      'TikTok event-subscription callback. Verifies the HMAC signature on the raw body and acknowledges receipt; downstream handlers are wired separately.',
  })
  callback(@Req() req: RawBodyRequest<Request>): { ok: true } {
    if (!this.env.TIKTOK_CLIENT_SECRET) {
      throw new ServiceUnavailableException(
        'TikTok webhook secret not configured on the server',
      );
    }

    const signatureHeader =
      (req.headers['tiktok-signature'] as string | undefined) ??
      (req.headers['TikTok-Signature'.toLowerCase()] as string | undefined);

    const rawBody =
      req.rawBody !== undefined ? req.rawBody.toString('utf8') : '';

    try {
      const envelope = this.verifier.verifyAndParse({
        signatureHeader,
        rawBody,
      });
      this.logger.log(
        `tiktok webhook event=${envelope.event} user=${envelope.user_openid ?? '-'}`,
      );
    } catch (err) {
      if (err instanceof TikTokWebhookVerificationError) {
        throw new UnauthorizedException(err.message);
      }
      throw err;
    }
    return { ok: true };
  }
}
