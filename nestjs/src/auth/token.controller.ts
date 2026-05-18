import { Controller, Headers, Post } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiCreatedResponse } from '@nestjs/swagger';
import { AccessTokenMinter } from './access-token.minter';
import { BasicCredentialsExtractor } from './basic-credentials.extractor';
import { AccessTokenResponseDto } from './access-token-response.dto';
import { Public } from './public.decorator';

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
    const member = await this.extractor.extract(headers);
    const token = await this.minter.mint(String(member.id));
    return new AccessTokenResponseDto(token, 'Bearer', this.minter.ttl());
  }
}
