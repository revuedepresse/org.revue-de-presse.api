import { Controller, Get, Header, Headers, Query, Req, UseInterceptors } from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import type { Request } from 'express';
import { HighlightsService } from '@/core/highlights/highlights.service';
import { hydraCollection } from '@/core/highlights/hydra.serializer';
import { CacheLabelInterceptor } from '@/common/cache-label.interceptor';

@ApiTags('highlights')
@ApiBearerAuth()
@Controller('highlights')
@UseInterceptors(CacheLabelInterceptor)
export class HighlightsController {
  constructor(private readonly service: HighlightsService) {}

  @Get()
  @Header('Cache-Control', 'max-age=3600, public, s-maxage=3600')
  @Header('Vary', 'Accept, Authorization')
  @ApiOperation({ summary: 'List highlights for a date range' })
  async list(
    @Req() req: Request,
    @Query() query: Record<string, string>,
    @Headers('accept') accept: string | undefined,
  ): Promise<unknown> {
    const wrapped = {
      query: query as never,
      headers: req.headers as never,
      _highlights_cache: undefined as string | undefined,
    };
    const items = await this.service.list(wrapped);
    // The service stamps `_highlights_cache` on `wrapped`. Forward it to the
    // real Express request so CacheLabelInterceptor can copy it to `x-cache`.
    if (wrapped._highlights_cache !== undefined) {
      (req as unknown as { _highlights_cache?: string })._highlights_cache = wrapped._highlights_cache;
    }

    const page = Number(query.page ?? 1);
    const itemsPerPage = Math.min(100, Number(query.itemsPerPage ?? 25));
    const totalItems = items.length;
    const queryString = new URLSearchParams(query).toString();

    const envelope = hydraCollection({
      contextPath: '/api/contexts/Highlight',
      iri: '/api/highlights',
      items,
      page,
      itemsPerPage,
      totalItems,
      query: queryString,
    });

    if (accept && accept.includes('application/ld+json')) return envelope;
    return JSON.parse(JSON.stringify(envelope, (k, v) => (k.startsWith('@') ? undefined : v)));
  }
}
