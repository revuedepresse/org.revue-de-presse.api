import { Controller, Headers, Post, UnauthorizedException } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiCreatedResponse } from '@nestjs/swagger';
import { AccessTokenMinter } from '@/core/auth/access-token.minter';
import { BasicCredentialsExtractor } from '@/core/auth/basic-credentials.extractor';
import { AccessTokenResponseDto } from './access-token-response.dto';
import { Public } from './public.decorator';
import { InvalidClientCredentialsError } from '@/core/errors/invalid-client-credentials.error';

@ApiTags('auth')
@Controller('token')
export class TokenController {
  constructor(
    private readonly minter: AccessTokenMinter,
    private readonly extractor: BasicCredentialsExtractor,
  ) {}

  @Public()
  @Post()
  @ApiOperation({ summary: 'Mint a Bearer token from a Basic-auth API key' })
  @ApiCreatedResponse({ type: AccessTokenResponseDto })
  async mint(@Headers() headers: Record<string, string>): Promise<AccessTokenResponseDto> {
    try {
      const member = await this.extractor.extract(headers);
      const token = await this.minter.mint(String(member.id));
      return new AccessTokenResponseDto(token, 'Bearer', this.minter.ttl());
    } catch (err) {
      if (err instanceof InvalidClientCredentialsError) {
        throw new UnauthorizedException(err.message);
      }
      throw err;
    }
  }
}
