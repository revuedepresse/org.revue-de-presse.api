export class AccessTokenResponseDto {
  constructor(
    public readonly access_token: string,
    public readonly token_type: 'Bearer',
    public readonly expires_in: number,
  ) {}
}
