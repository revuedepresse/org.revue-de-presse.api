import { highlightCacheKey, sortedCsv, dateHourEuropeParis } from '@/core/highlights/cache-key';

describe('highlightCacheKey', () => {
  it('selectedAggregates order does not affect key', () => {
    const params = {
      startDate: new Date('2026-05-01T00:01:00'),
      endDate: new Date('2026-05-01T23:59:00'),
      selectedAggregates: ['b', 'a', 'c'],
    };
    const params2 = { ...params, selectedAggregates: ['c', 'a', 'b'] };
    expect(highlightCacheKey(params)).toBe(highlightCacheKey(params2));
  });

  it('distinct param combinations produce distinct keys', () => {
    const base = {
      startDate: new Date('2026-05-01'),
      endDate: new Date('2026-05-01'),
    };
    expect(highlightCacheKey({ ...base, distinctSources: true }))
      .not.toBe(highlightCacheKey({ ...base, distinctSources: false }));
  });

  it('truncates dates to hour precision', () => {
    const a = {
      startDate: new Date('2026-05-01T10:00:00'),
      endDate: new Date('2026-05-01T10:00:00'),
    };
    const b = {
      startDate: new Date('2026-05-01T10:59:00'),
      endDate: new Date('2026-05-01T10:00:00'),
    };
    expect(highlightCacheKey(a)).toBe(highlightCacheKey(b));
  });

  it('sortedCsv sorts strings and joins with comma', () => {
    expect(sortedCsv(['b', 'a', 'c'])).toBe('a,b,c');
    expect(sortedCsv([])).toBe('');
  });

  it('sortedCsv compares numeric strings numerically (PHP SORT_REGULAR parity)', () => {
    expect(sortedCsv(['42', '7', '100'])).toBe('7,42,100');
    expect(sortedCsv(['10', 'abc', '2'])).toBe('2,10,abc');
  });

  it('media flag is inverted (excludeMedia=true => media=0)', () => {
    const base = {
      startDate: new Date('2026-05-01T10:00:00'),
      endDate: new Date('2026-05-01T10:00:00'),
    };
    const k1 = highlightCacheKey({ ...base, excludeMedia: true });
    const k2 = highlightCacheKey({ ...base, excludeMedia: false });
    expect(k1).not.toBe(k2);
  });

  it('formats dateHour as Y-m-d HH in Europe/Paris with two-digit hour', () => {
    // 2026-01-15T08:00:00Z == 09:00 in Europe/Paris (UTC+1 in winter).
    const utc = new Date('2026-01-15T08:00:00Z');
    expect(dateHourEuropeParis(utc)).toBe('2026-01-15 09');
  });

  it('matches PHP sha1 fixtures byte-for-byte', () => {
    // Fixtures captured by running `tests/fixtures/cache-key-fixtures.php`
    // against the canonical PHP implementation. See doc/superpowers/specs.
    const cases: { params: Record<string, unknown>; expected: string }[] = [
      {
        params: {
          startDate: new Date('2024-01-01T00:00:00+01:00'),
          endDate: new Date('2024-01-01T23:00:00+01:00'),
          includeRetweets: false,
        },
        // PHP: sha1('2024-01-01 00;2024-01-01 23;page=1;items=25;rt=0;media=1;ds=0;term=;aggs=')
        expected: '4d908a0feb3e64400e210dee86340a4fa5fe5dbd',
      },
      {
        params: {
          startDate: new Date('2026-05-01T08:30:00+02:00'),  // Paris DST, 08:30 local
          endDate: new Date('2026-05-01T12:00:00+02:00'),
          distinctSources: true,
          selectedAggregates: ['political', 'cultural'],  // intentionally out of order
          term: 'macron',
        },
        // PHP: sha1('2026-05-01 08;2026-05-01 12;page=1;items=25;rt=0;media=1;ds=1;term=macron;aggs=cultural,political')
        expected: '903d316c6bfbf57e5bc7c7bef012a05df5e09043',
      },
    ];
    for (const { params, expected } of cases) {
      expect(highlightCacheKey(params as never)).toBe(expected);
    }
  });
});
