import { Module, OnApplicationShutdown, Inject } from '@nestjs/common';
import { drizzle as drizzlePg, NodePgDatabase } from 'drizzle-orm/node-postgres';
import { drizzle as drizzleSqlite, BetterSQLite3Database } from 'drizzle-orm/better-sqlite3';
import * as pg from 'pg';
import Database from 'better-sqlite3';
import { DB } from './db.tokens';
import { ENV } from '@/config/env';
import type { Env } from '@/config/env';
import * as schema from './schema';

export type Db = NodePgDatabase<typeof schema> | BetterSQLite3Database<typeof schema>;

type Resources = { pgPool?: pg.Pool; sqlite?: Database.Database };
const RES = Symbol('DB_RES');

@Module({
  providers: [
    {
      provide: DB,
      useFactory: (env: Env, res: Resources): Db => {
        const url = new URL(env.DATABASE_URL);
        if (url.protocol === 'postgresql:' || url.protocol === 'postgres:') {
          res.pgPool = new pg.Pool({
            connectionString: env.DATABASE_URL,
            min: env.PG_POOL_MIN ?? 1,
            max: env.PG_POOL_MAX ?? 10,
          });
          return drizzlePg(res.pgPool, { schema });
        }
        if (url.protocol === 'sqlite:') {
          const dbPath = url.pathname === '/:memory:' ? ':memory:' : url.pathname;
          res.sqlite = new Database(dbPath);
          return drizzleSqlite(res.sqlite, { schema });
        }
        throw new Error(`Unsupported DATABASE_URL protocol: ${url.protocol}`);
      },
      inject: [ENV, { token: RES, optional: false }],
    },
    { provide: RES, useValue: { pgPool: undefined, sqlite: undefined } as Resources },
  ],
  exports: [DB, RES],
})
export class DbModule implements OnApplicationShutdown {
  constructor(@Inject(RES) private readonly res: Resources) {}
  async onApplicationShutdown(): Promise<void> {
    await this.res.pgPool?.end();
    this.res.sqlite?.close();
  }
}
