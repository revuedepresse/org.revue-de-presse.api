import { HighlightFilters } from '@/core/highlights/highlight-filters';

describe('HighlightFilters', () => {
  const filters = new HighlightFilters();

  it('no filters returns input unchanged', () => {
    const input = [
      { screen_name: 'a', text: 'foo' },
      { screen_name: 'b', text: 'bar' },
    ];
    expect(filters.apply(input, {})).toEqual(input);
  });

  it('distinctSources dedups by screen_name keeping first', () => {
    const input = [
      { screen_name: 'a', text: 'first-a' },
      { screen_name: 'b', text: 'first-b' },
      { screen_name: 'a', text: 'second-a' },
    ];
    const out = filters.apply(input, { distinctSources: true });
    expect(out).toHaveLength(2);
    expect(out[0].text).toBe('first-a');
    expect(out[1].text).toBe('first-b');
  });

  it('term does case-insensitive substring match', () => {
    const input = [
      { screen_name: 'a', text: 'About politics' },
      { screen_name: 'b', text: 'About sport' },
    ];
    const out = filters.apply(input, { term: 'POLITICS' });
    expect(out).toHaveLength(1);
    expect(out[0].screen_name).toBe('a');
  });

  it('selectedAggregates filters to listed aggregates only', () => {
    const input = [
      { screen_name: 'a', aggregate: 'cultural' },
      { screen_name: 'b', aggregate: 'political' },
      { screen_name: 'c', aggregate: 'cultural' },
    ];
    expect(filters.apply(input, { selectedAggregates: ['cultural'] })).toHaveLength(2);
  });
});
