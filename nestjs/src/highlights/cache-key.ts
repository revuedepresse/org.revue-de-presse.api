import { createHash } from 'node:crypto';

export interface CacheKeyParams {
  startDate?: Date | null;
  endDate?: Date | null;
  page?: number;
  itemsPerPage?: number;
  includeRetweets?: boolean;
  excludeMedia?: boolean;
  distinctSources?: boolean;
  term?: string;
  selectedAggregates?: string[];
}

const PARIS_TZ = 'Europe/Paris';

export function dateHourEuropeParis(d: Date | null | undefined): string {
  if (!d || !(d instanceof Date) || isNaN(d.getTime())) return '';
  const fmt = new Intl.DateTimeFormat('en-CA', {
    timeZone: PARIS_TZ,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    hour12: false,
  });
  const parts = Object.fromEntries(fmt.formatToParts(d).map((p) => [p.type, p.value]));
  // Intl returns hour '24' at midnight on some runtimes; normalize to '00'.
  const hour = parts.hour === '24' ? '00' : parts.hour;
  return `${parts.year}-${parts.month}-${parts.day} ${hour}`;
}

export function sortedCsv(values: string[]): string {
  return [...values].map((v) => String(v)).sort().join(',');
}

export function highlightCacheKey(p: CacheKeyParams): string {
  const parts = [
    dateHourEuropeParis(p.startDate ?? null),
    dateHourEuropeParis(p.endDate ?? null),
    `page=${p.page ?? 1}`,
    `items=${p.itemsPerPage ?? 25}`,
    `rt=${p.includeRetweets ? 1 : 0}`,
    `media=${p.excludeMedia ? 0 : 1}`, // inverted
    `ds=${p.distinctSources ? 1 : 0}`,
    `term=${p.term ?? ''}`,
    `aggs=${sortedCsv(p.selectedAggregates ?? [])}`,
  ];
  return createHash('sha1').update(parts.join(';')).digest('hex');
}
