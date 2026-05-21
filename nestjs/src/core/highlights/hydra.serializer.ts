import { HighlightDto } from './highlight.dto';

export interface HydraCollectionInput {
  contextPath: string;
  iri: string;
  items: HighlightDto[];
  page: number;
  itemsPerPage: number;
  totalItems: number;
  query: string;
}

export interface HydraCollection {
  '@context': string;
  '@id': string;
  '@type': 'Collection';
  totalItems: number;
  member: Array<HighlightDto & { '@id': string; '@type': 'Highlight' }>;
  view: {
    '@id': string;
    '@type': 'PartialCollectionView';
    first: string;
    last: string;
    previous: string | null;
    next: string | null;
  };
  search: null;
}

export function hydraCollection(input: HydraCollectionInput): HydraCollection {
  const { iri, items, page, itemsPerPage, totalItems, query, contextPath } = input;
  const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));
  const buildIri = (p: number) => {
    const params = new URLSearchParams(query);
    params.set('page', String(p));
    return `${iri}?${params.toString()}`;
  };

  return {
    '@context': contextPath,
    '@id': iri,
    '@type': 'Collection',
    totalItems,
    member: items.map((d) => ({ '@id': `${iri}/${d.publicationId}`, '@type': 'Highlight', ...d })),
    view: {
      '@id': buildIri(page),
      '@type': 'PartialCollectionView',
      first: buildIri(1),
      last: buildIri(totalPages),
      previous: page > 1 ? buildIri(page - 1) : null,
      next: page < totalPages ? buildIri(page + 1) : null,
    },
    search: null,
  };
}
