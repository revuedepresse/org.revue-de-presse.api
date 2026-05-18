import { Module } from '@nestjs/common';
import { DbModule } from '@/db/db.module';
import { DB } from '@/db/db.tokens';
import type { Db } from '@/db/db.module';
import { MembersRepository } from './members.repository';

@Module({
  imports: [DbModule],
  providers: [
    {
      provide: MembersRepository,
      useFactory: (db: Db) => new MembersRepository(db),
      inject: [DB],
    },
  ],
  exports: [MembersRepository],
})
export class MembersModule {}
