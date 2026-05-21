export interface HighlightDto {
  publicationId: string;
  screenName: string;
  avatarUrl: string | null;
  text: string;
  reposts: number;
  likes: number;
  replies: number;
  date: Date;
  url: string;
}
