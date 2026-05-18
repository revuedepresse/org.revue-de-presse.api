import { Controller, Get, Header, Options, HttpCode, HttpStatus } from '@nestjs/common';
import { ApiTags } from '@nestjs/swagger';
import { Public } from '@/adapters/nestjs/auth/public.decorator';

@ApiTags('health')
@Public()
@Controller('healthcheck')
export class HealthcheckController {
  @Get()
  @Header('Cache-Control', 'no-store')
  get(): unknown[] { return []; }

  @Options()
  @HttpCode(HttpStatus.OK)
  options(): void { /* CORS preflight handled by app.enableCors */ }
}
