import { HighlightsService } from '@/core/highlights/highlights.service';
import { HighlightFilters } from '@/core/highlights/highlight-filters';
import { HighlightNormalizer } from '@/core/highlights/highlight-normalizer';
import { InMemorySnapshotReader } from '@test/doubles/in-memory-snapshot-reader';

function makeReq(query: Record<string, string> = {}, headers: Record<string, string> = {}) {
  return { query, headers, _highlights_cache: undefined as string | undefined };
}

describe('HighlightsService', () => {
  it('returns normalized DTOs from snapshot when Redis is absent (unknown label)', async () => {
    const reader = new InMemorySnapshotReader({
      '2026-05-01': { statuses: [{
        screen_name: 'a', reposts: 1, likes: 2, text: 'hi',
        publication_id: 'at://did/x/p1', publicationDateTime: '2026-05-01T10:00:00+02:00',
      }] },
    });
    const service = new HighlightsService(reader, new HighlightFilters(), new HighlightNormalizer(), null, 'test');
    const req = makeReq({ startDate: '2026-05-01', endDate: '2026-05-01', includeRetweets: '0' });
    const items = await service.list(req);
    expect(items).toHaveLength(1);
    expect(items[0].screenName).toBe('a');
    expect(req._highlights_cache).toBe('unknown');
  });

  it('empty snapshot returns empty collection', async () => {
    const reader = new InMemorySnapshotReader();
    const service = new HighlightsService(reader, new HighlightFilters(), new HighlightNormalizer(), null, 'test');
    const req = makeReq({ startDate: '1999-01-01', endDate: '1999-01-01', includeRetweets: '0' });
    expect(await service.list(req)).toEqual([]);
  });

  it('x-benchmark bypasses Redis in non-prod', async () => {
    const reader = new InMemorySnapshotReader({ '2026-05-01': { statuses: [] } });
    const redisMock = { get: jest.fn(), setex: jest.fn() };
    const service = new HighlightsService(reader, new HighlightFilters(), new HighlightNormalizer(), redisMock as never, 'test');
    const req = makeReq({ startDate: '2026-05-01', endDate: '2026-05-01', includeRetweets: '0' }, { 'x-benchmark': '1' });
    await service.list(req);
    expect(req._highlights_cache).toBe('bypass');
  });

  it('returns hit and uses cached payload when Redis has the key', async () => {
    const reader = new InMemorySnapshotReader({});
    const cached = JSON.stringify([{
      publicationId: 'at://x', screenName: 'a', avatarUrl: null, text: 'cached',
      reposts: 1, likes: 2, replies: 0, date: '2026-05-01T10:00:00+02:00', url: 'https://x',
    }]);
    const redisMock = { get: jest.fn().mockResolvedValue(cached), setex: jest.fn() };
    const service = new HighlightsService(reader, new HighlightFilters(), new HighlightNormalizer(), redisMock as never, 'test');
    const req = makeReq({ startDate: '2026-05-01', endDate: '2026-05-01' });
    const items = await service.list(req);
    expect(req._highlights_cache).toBe('hit');
    expect(items[0].text).toBe('cached');
  });

  it('marks miss and writes back with TTL 3600 when key not found', async () => {
    const reader = new InMemorySnapshotReader({ '2026-05-01': { statuses: [] } });
    const redisMock = { get: jest.fn().mockResolvedValue(null), setex: jest.fn().mockResolvedValue('OK') };
    const service = new HighlightsService(reader, new HighlightFilters(), new HighlightNormalizer(), redisMock as never, 'test');
    const req = makeReq({ startDate: '2026-05-01', endDate: '2026-05-01' });
    await service.list(req);
    expect(req._highlights_cache).toBe('miss');
    expect(redisMock.setex).toHaveBeenCalledWith(expect.stringMatching(/^highlights:items:[0-9a-f]{40}$/), 3600, expect.any(String));
  });

  it('falls back to fresh with error label when Redis throws', async () => {
    const reader = new InMemorySnapshotReader({ '2026-05-01': { statuses: [] } });
    const redisMock = { get: jest.fn().mockRejectedValue(new Error('boom')) };
    const service = new HighlightsService(reader, new HighlightFilters(), new HighlightNormalizer(), redisMock as never, 'test');
    const req = makeReq({ startDate: '2026-05-01', endDate: '2026-05-01' });
    const items = await service.list(req);
    expect(req._highlights_cache).toBe('error');
    expect(items).toEqual([]);
  });
});
