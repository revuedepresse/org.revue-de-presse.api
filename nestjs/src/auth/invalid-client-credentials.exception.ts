import { HttpException, HttpStatus } from '@nestjs/common';

export class InvalidClientCredentialsException extends HttpException {
  constructor(message = 'Invalid client credentials') {
    super({ statusCode: 401, message }, HttpStatus.UNAUTHORIZED);
  }
}
