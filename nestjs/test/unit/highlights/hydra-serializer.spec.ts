import { hydraCollection } from '@/core/highlights/hydra.serializer';

describe('hydraCollection', () => {
  it('builds the collection envelope with @type Collection', () => {
    const out = hydraCollection({
      contextPath: '/api/contexts/Highlight',
      iri: '/api/highlights',
      items: [{
        publicationId: 'pid', screenName: 'a', avatarUrl: null, text: 't',
        reposts: 0, likes: 0, replies: 0, date: new Date('2026-05-17T08:00:00+02:00'), url: 'https://x',
      }],
      page: 1,
      itemsPerPage: 25,
      totalItems: 1,
      query: 'startDate=2026-05-01',
    });
    expect(out['@type']).toBe('Collection');
    expect(out['@context']).toBe('/api/contexts/Highlight');
    expect(out['@id']).toBe('/api/highlights');
    expect(out.totalItems).toBe(1);
    expect(out.member[0]['@type']).toBe('Highlight');
    expect(out.member[0]['@id']).toBe('/api/highlights/pid');
    expect(out.view['@type']).toBe('PartialCollectionView');
  });

  it('plain object on Accept: application/json strips @-prefixed keys', () => {
    const out = hydraCollection({
      contextPath: '/api/contexts/Highlight',
      iri: '/api/highlights',
      items: [],
      page: 1, itemsPerPage: 25, totalItems: 0, query: '',
    });
    const plain = JSON.parse(JSON.stringify(out, (k, v) => (k.startsWith('@') ? undefined : v)));
    expect(plain['@type']).toBeUndefined();
    expect(plain.totalItems).toBe(0);
    expect(plain.member).toEqual([]);
  });
});
