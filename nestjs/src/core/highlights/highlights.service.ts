import type { Env } from '@/config/env';
import { SnapshotReader } from './snapshot-reader';
import { HighlightFilters, FilterParams } from './highlight-filters';
import { HighlightNormalizer } from './highlight-normalizer';
import { HighlightDto } from './highlight.dto';
import { highlightCacheKey, CacheKeyParams } from './cache-key';
import { Logger, NoopLogger } from '@/core/ports/logger';
import type { KeyValueCache } from '@/core/ports/cache';

export interface HighlightsRequest {
  query: Record<string, string | string[] | undefined>;
  headers: Record<string, string | string[] | undefined>;
  _highlights_cache?: string;
}

export class HighlightsService {
  constructor(
    private readonly reader: SnapshotReader,
    private readonly filters: HighlightFilters,
    private readonly normalizer: HighlightNormalizer,
    private readonly redis: KeyValueCache | null,
    private readonly appEnvOrEnv: Env | string,
    private readonly logger: Logger = new NoopLogger(),
  ) {}

  private appEnv(): string {
    return typeof this.appEnvOrEnv === 'string' ? this.appEnvOrEnv : this.appEnvOrEnv.APP_ENV;
  }

  async list(req: HighlightsRequest): Promise<HighlightDto[]> {
    const params = parseQuery(req.query);
    const bypass = req.headers['x-benchmark'] !== undefined && this.appEnv() !== 'prod';

    if (bypass) { req._highlights_cache = 'bypass'; return this.loadFresh(params); }
    if (!this.redis) { req._highlights_cache = 'unknown'; return this.loadFresh(params); }

    const key = 'highlights:items:' + highlightCacheKey(params);
    try {
      const cached = await this.redis.get(key);
      if (cached) {
        req._highlights_cache = 'hit';
        return JSON.parse(cached).map((r: Record<string, unknown>) => ({
          ...r,
          avatarUrl: r.avatarUrl == null ? null : String(r.avatarUrl),
          date: new Date(String(r.date ?? '')),
        })) as HighlightDto[];
      }
      req._highlights_cache = 'miss';
      const fresh = await this.loadFresh(params);
      await this.redis.setex(key, 3600, JSON.stringify(fresh.map((d) => ({ ...d, date: d.date.toISOString() }))));
      return fresh;
    } catch (err) {
      this.logger.warn({ msg: 'redis read-through unavailable', error: (err as Error).message }, 'HighlightsService');
      req._highlights_cache = 'error';
      return this.loadFresh(params);
    }
  }

  private async loadFresh(params: CacheKeyParams & FilterParams): Promise<HighlightDto[]> {
    const date = params.startDate instanceof Date && !isNaN(params.startDate.getTime())
      ? formatDateParis(params.startDate)
      : 'unknown';
    const snapshot = await this.reader.read(date);
    const statuses = Array.isArray(snapshot)
      ? snapshot
      : (snapshot && Array.isArray((snapshot as { statuses?: unknown[] }).statuses)
        ? (snapshot as { statuses: unknown[] }).statuses
        : []);
    const filtered = this.filters.apply(statuses as Record<string, unknown>[], params);
    return filtered.map((raw) => this.normalizer.toDto(raw));
  }
}

function formatDateParis(d: Date): string {
  const fmt = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Europe/Paris',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
  return fmt.format(d); // returns YYYY-MM-DD
}

function parseQuery(query: Record<string, string | string[] | undefined>): CacheKeyParams & FilterParams {
  const one = (v: string | string[] | undefined) => Array.isArray(v) ? v[0] : v;
  const parseDate = (raw: string | undefined): Date | null => {
    if (!raw) return null;
    // PHP parses in Europe/Paris with .setTime(0, 1) for startOfDay equivalence.
    const d = new Date(`${raw}T00:01:00+02:00`);
    return isNaN(d.getTime()) ? null : d;
  };
  const csv = (v: string | string[] | undefined): string[] => {
    if (Array.isArray(v)) return v.map(String);
    if (typeof v === 'string' && v !== '') return v.split(',');
    return [];
  };
  const bool = (v: string | string[] | undefined) => {
    const s = one(v);
    return s === '1' || s === 'true';
  };
  return {
    startDate: parseDate(one(query.startDate)),
    endDate: parseDate(one(query.endDate)),
    page: query.page ? Number(one(query.page)) : 1,
    itemsPerPage: query.itemsPerPage ? Number(one(query.itemsPerPage)) : 25,
    includeRetweets: bool(query.includeRetweets),
    excludeMedia: bool(query.excludeMedia),
    distinctSources: bool(query.distinctSources),
    term: typeof one(query.term) === 'string' ? (one(query.term) as string) : '',
    selectedAggregates: csv(query['selectedAggregates[]'] ?? query.selectedAggregates),
  };
}
