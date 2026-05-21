/**
 * Thrown by the TikTok OAuth client when the upstream returns a 4xx response
 * during the `authorization_code` exchange. The raw upstream body is preserved
 * so callers can surface the TikTok-side detail back to the maintainer.
 */
export class TikTokExchangeError extends Error {
  constructor(
    public readonly status: number,
    public readonly body: unknown,
    message = 'TikTok rejected the authorization_code exchange',
  ) {
    super(message);
    this.name = 'TikTokExchangeError';
  }
}

/**
 * Thrown when the upstream returns 2xx but the body does not match the
 * documented TokenResponse shape.
 */
export class TikTokInvalidResponseError extends Error {
  constructor(public readonly issues: string, public readonly body: unknown) {
    super(`Unexpected TikTok token response shape: ${issues}`);
    this.name = 'TikTokInvalidResponseError';
  }
}
