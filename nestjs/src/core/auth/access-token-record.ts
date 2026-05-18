export class AccessTokenRecord {
  constructor(
    public readonly memberId: string,
    public readonly issuedAt: Date,
    public readonly expiresAt: Date,
  ) {}

  isExpired(now: Date = new Date()): boolean {
    return this.expiresAt.getTime() <= now.getTime();
  }
}
