import {
  BadRequestException,
  Body,
  Controller,
  Get,
  Header,
  HttpCode,
  HttpStatus,
  Inject,
  Post,
  Query,
  Req,
  Res,
  ServiceUnavailableException,
} from '@nestjs/common';
import {
  ApiBearerAuth,
  ApiOkResponse,
  ApiOperation,
  ApiTags,
} from '@nestjs/swagger';
import type { Request, Response } from 'express';
import { Public } from '@/adapters/nestjs/auth/public.decorator';
import { ENV } from '@/config/env';
import type { Env } from '@/config/env';
import {
  TIKTOK_OAUTH_CLIENT,
  TikTokOAuthClient,
} from '@/core/tiktok/tiktok-oauth.client';
import {
  TikTokExchangeError,
  TikTokInvalidResponseError,
} from '@/core/tiktok/tiktok-oauth.errors';
import {
  TikTokExchangeRequestSchema,
  type TikTokTokenResponse,
} from '@/core/tiktok/tiktok-oauth.types';
import { TikTokOAuthExchangeDto } from './tiktok-oauth-exchange.dto';

/**
 * HTML escape every value rendered into the callback page so no
 * user-controlled string (code, state, URL) reaches the browser as raw HTML.
 * Inline + dependency-free by design.
 */
function escapeHtml(input: string): string {
  return input
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function renderCallbackHtml(args: {
  code: string;
  state: string;
  fullUrl: string;
}): string {
  const code = escapeHtml(args.code);
  const state = escapeHtml(args.state);
  const url = escapeHtml(args.fullUrl);
  return `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>TikTok OAuth callback</title>
  <meta name="robots" content="noindex" />
  <style>
    body { font-family: system-ui, -apple-system, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
    h1 { font-size: 1.25rem; }
    pre, textarea { background: #f4f4f4; padding: 0.75rem; border-radius: 4px; font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 0.875rem; }
    textarea { width: 100%; box-sizing: border-box; min-height: 5rem; }
    .label { font-weight: 600; margin-top: 1rem; }
  </style>
</head>
<body>
  <h1>TikTok OAuth callback received</h1>
  <p>Paste this URL back into the bootstrap CLI:</p>
  <textarea readonly aria-label="Callback URL">${url}</textarea>
  <p class="label">code</p>
  <pre>${code}</pre>
  <p class="label">state</p>
  <pre>${state}</pre>
</body>
</html>`;
}

@ApiTags('tiktok')
@Controller('tiktok/oauth')
export class TikTokOAuthController {
  constructor(
    @Inject(ENV) private readonly env: Env,
    @Inject(TIKTOK_OAUTH_CLIENT) private readonly client: TikTokOAuthClient,
  ) {}

  @Public()
  @Get('callback')
  @Header('Cache-Control', 'no-store')
  @ApiOperation({
    summary:
      'Display the TikTok OAuth callback values so the maintainer can paste them into the bootstrap CLI.',
  })
  callback(
    @Req() req: Request,
    @Res() res: Response,
    @Query('code') code: string | undefined,
    @Query('state') state: string | undefined,
    @Query('error') error: string | undefined,
    @Query('error_description') errorDescription: string | undefined,
  ): void {
    // Always re-assert no-store so an upstream proxy cannot cache this page.
    res.setHeader('Cache-Control', 'no-store');

    if (error) {
      const detail = errorDescription
        ? `${error}: ${errorDescription}`
        : error;
      res.status(HttpStatus.BAD_REQUEST);
      res.setHeader('Content-Type', 'application/problem+json');
      res.json({
        type: 'about:blank',
        title: 'TikTok OAuth error',
        status: HttpStatus.BAD_REQUEST,
        detail,
      });
      return;
    }

    if (!code || !state) {
      res.status(HttpStatus.BAD_REQUEST);
      res.setHeader('Content-Type', 'application/problem+json');
      res.json({
        type: 'about:blank',
        title: 'Missing OAuth parameters',
        status: HttpStatus.BAD_REQUEST,
        detail:
          'Both `code` and `state` query parameters are required on the TikTok OAuth callback.',
      });
      return;
    }

    const host = req.headers.host ?? 'api.revue-de-presse.org';
    const protocol = (req.headers['x-forwarded-proto'] as string | undefined) ?? req.protocol ?? 'https';
    const fullUrl = `${protocol}://${host}${req.originalUrl}`;
    const html = renderCallbackHtml({ code, state, fullUrl });

    res.status(HttpStatus.OK);
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    res.send(html);
  }

  @Post('exchange')
  @HttpCode(HttpStatus.OK)
  @ApiBearerAuth()
  @ApiOperation({
    summary:
      'Server-side exchange of an authorization_code for TikTok access + refresh tokens.',
  })
  @ApiOkResponse({
    description: 'Upstream TikTok token response.',
    schema: {
      type: 'object',
      properties: {
        access_token: { type: 'string' },
        refresh_token: { type: 'string' },
        expires_in: { type: 'integer' },
        refresh_expires_in: { type: 'integer' },
        scope: { type: 'string' },
        open_id: { type: 'string' },
      },
    },
  })
  async exchange(@Body() body: TikTokOAuthExchangeDto): Promise<TikTokTokenResponse> {
    if (!this.env.TIKTOK_CLIENT_KEY || !this.env.TIKTOK_CLIENT_SECRET) {
      throw new ServiceUnavailableException(
        'TikTok credentials not configured on the server',
      );
    }

    const parsed = TikTokExchangeRequestSchema.safeParse(body);
    if (!parsed.success) {
      const detail = parsed.error.issues
        .map((i) => `${i.path.join('.')}: ${i.message}`)
        .join('; ');
      throw new BadRequestException(detail);
    }

    try {
      return await this.client.exchangeAuthorizationCode({
        code: parsed.data.code,
        codeVerifier: parsed.data.code_verifier,
        redirectUri: parsed.data.redirect_uri,
      });
    } catch (err) {
      if (err instanceof TikTokExchangeError) {
        throw new BadRequestException({
          detail:
            err.body !== null && err.body !== undefined
              ? typeof err.body === 'string'
                ? err.body
                : JSON.stringify(err.body)
              : err.message,
        });
      }
      if (err instanceof TikTokInvalidResponseError) {
        throw new BadRequestException({ detail: err.message });
      }
      throw err;
    }
  }
}
