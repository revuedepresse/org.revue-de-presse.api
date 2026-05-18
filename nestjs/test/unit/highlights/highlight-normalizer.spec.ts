import { HighlightNormalizer } from '@/highlights/highlight-normalizer';

describe('HighlightNormalizer', () => {
  it('bluesky shape payload yields bsky URL', () => {
    const n = new HighlightNormalizer();
    const dto = n.toDto({
      screen_name: 'lemonde.fr',
      reposts: 12,
      likes: 34,
      avatar_url: 'https://cdn/avatar.jpg',
      text: 'a post',
      publication_id: 'at://did:plc:abc/x/post-id-123',
      publicationDateTime: '2026-05-01T10:00:00+02:00',
    });
    expect(dto.screenName).toBe('lemonde.fr');
    expect(dto.reposts).toBe(12);
    expect(dto.url).toBe('https://bsky.app/profile/lemonde.fr/post/post-id-123');
  });

  it('missing avatar_url yields null', () => {
    const dto = new HighlightNormalizer().toDto({
      screen_name: 'lemonde.fr',
      reposts: 0,
      likes: 0,
      text: 'a',
      publication_id: 'at://did/x/p',
      publicationDateTime: '2026-05-01T10:00:00+02:00',
    });
    expect(dto.avatarUrl).toBeNull();
  });

  it('explicit url is preferred over derived', () => {
    const dto = new HighlightNormalizer().toDto({
      screen_name: 'lemonde.fr',
      publication_id: 'at://did/x/p',
      url: 'https://override.example/x',
      text: 'a',
      publicationDateTime: '2026-05-01T10:00:00+02:00',
    });
    expect(dto.url).toBe('https://override.example/x');
  });
});
