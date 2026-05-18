import { Module } from '@nestjs/common';
import { HealthcheckController } from './health.controller';

@Module({ controllers: [HealthcheckController] })
export class HealthModule {}
