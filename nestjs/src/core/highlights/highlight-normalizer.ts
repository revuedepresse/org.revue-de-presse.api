import { HighlightDto } from './highlight.dto';

type Raw = Record<string, unknown>;

export class HighlightNormalizer {
  toDto(raw: Raw): HighlightDto {
    const publicationId = String(raw.publication_id ?? '');
    const screenName = String(raw.screen_name ?? '');
    const url = this.buildUrl(publicationId, screenName, raw.url);
    const date = this.parseDate(String(raw.publicationDateTime ?? raw.date ?? new Date().toISOString()));

    return {
      publicationId,
      screenName,
      avatarUrl: raw.avatar_url !== undefined && raw.avatar_url !== null ? String(raw.avatar_url) : null,
      text: String(raw.text ?? ''),
      reposts: Number(raw.reposts ?? 0),
      likes: Number(raw.likes ?? 0),
      replies: Number(raw.replies ?? 0),
      date,
      url,
    };
  }

  private buildUrl(publicationId: string, screenName: string, explicit: unknown): string {
    if (typeof explicit === 'string' && explicit !== '') return explicit;
    if (publicationId.startsWith('at://')) {
      const tail = publicationId.split('/').pop() ?? '';
      return `https://bsky.app/profile/${screenName}/post/${tail}`;
    }
    return publicationId;
  }

  private parseDate(raw: string): Date {
    const d = new Date(raw);
    return isNaN(d.getTime()) ? new Date() : d;
  }
}
