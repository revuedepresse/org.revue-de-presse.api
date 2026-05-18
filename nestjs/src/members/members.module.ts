import { Module } from '@nestjs/common';
import { DbModule } from '@/db/db.module';
import { MembersRepository } from './members.repository';

@Module({
  imports: [DbModule],
  providers: [MembersRepository],
  exports: [MembersRepository],
})
export class MembersModule {}
