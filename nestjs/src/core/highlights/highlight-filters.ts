export type RawStatus = Record<string, unknown>;
export interface FilterParams {
  distinctSources?: boolean;
  term?: string;
  selectedAggregates?: string[];
}

export class HighlightFilters {
  apply(items: RawStatus[], params: FilterParams): RawStatus[] {
    let out = items;

    if (params.distinctSources) {
      const seen = new Set<string>();
      out = out.filter((it) => {
        const v = it.screen_name;
        if (typeof v !== 'string' || seen.has(v)) return false;
        seen.add(v);
        return true;
      });
    }

    if (params.term && params.term !== '') {
      const needle = params.term.toLowerCase();
      out = out.filter((it) => typeof it.text === 'string' && it.text.toLowerCase().includes(needle));
    }

    const selected = params.selectedAggregates;
    if (Array.isArray(selected) && selected.length > 0) {
      out = out.filter((it) => typeof it.aggregate === 'string' && selected.includes(it.aggregate));
    }

    return out;
  }
}
