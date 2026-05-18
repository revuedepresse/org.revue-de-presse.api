export class InvalidClientCredentialsError extends Error {
  constructor(message = 'Invalid client credentials') {
    super(message);
    this.name = 'InvalidClientCredentialsError';
  }
}
