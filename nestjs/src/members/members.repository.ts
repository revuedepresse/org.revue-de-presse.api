import { Inject, Injectable } from '@nestjs/common';
import { and, eq } from 'drizzle-orm';
import { BetterSQLite3Database } from 'drizzle-orm/better-sqlite3';
import { DB } from '@/db/db.tokens';
import type { Db } from '@/db/db.module';
import * as schema from '@/db/schema';
import { weavingUser } from '@/db/schema';
import { Member } from './member.entity';

// The Db union (NodePgDatabase | BetterSQLite3Database) has incompatible .select() overloads
// at the TypeScript level. We cast to the sqlite variant (which is the test-time driver) so
// the compiler is happy; at runtime the pg driver exposes the same API surface.
type AnyDb = BetterSQLite3Database<typeof schema>;

@Injectable()
export class MembersRepository {
  constructor(@Inject(DB) private readonly db: Db) {}

  private get anyDb(): AnyDb {
    return this.db as unknown as AnyDb;
  }

  async findEnabledByApiKey(secret: string): Promise<Member | null> {
    const rows = await this.anyDb
      .select()
      .from(weavingUser)
      .where(and(eq(weavingUser.enabled, true), eq(weavingUser.apiKey, secret)))
      .limit(1);
    return rows[0] ? this.toEntity(rows[0]) : null;
  }

  async findById(id: number): Promise<Member | null> {
    const rows = await this.anyDb
      .select()
      .from(weavingUser)
      .where(eq(weavingUser.id, id))
      .limit(1);
    return rows[0] ? this.toEntity(rows[0]) : null;
  }

  private toEntity(row: typeof weavingUser.$inferSelect): Member {
    return {
      id: row.id,
      apiKey: row.apiKey,
      username: row.username,
      usernameCanonical: row.usernameCanonical,
      isEnabled: row.enabled,
    };
  }
}
