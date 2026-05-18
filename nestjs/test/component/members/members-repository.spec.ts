import Database from 'better-sqlite3';
import { drizzle } from 'drizzle-orm/better-sqlite3';
import { DrizzleMembersRepository } from '@/adapters/persistence/drizzle/drizzle-members.repository';
import { bootstrapSqlite } from '@test/setup/sqlite-bootstrap';
import * as schema from '@/adapters/persistence/drizzle/schema';

describe('MembersRepository', () => {
  function setup() {
    const sqlite = new Database(':memory:');
    bootstrapSqlite(sqlite);
    const db = drizzle(sqlite, { schema });
    const repo = new DrizzleMembersRepository(db as never);
    return { db, repo, sqlite };
  }

  it('findEnabledByApiKey returns the enabled member with matching api key', async () => {
    const { repo, sqlite } = setup();
    sqlite.prepare(
      `INSERT INTO weaving_user (usr_api_key, usr_user_name, usr_status) VALUES (?, ?, 1)`,
    ).run('secret-abc', 'alice');
    const member = await repo.findEnabledByApiKey('secret-abc');
    expect(member).not.toBeNull();
    expect(member?.username).toBe('alice');
    expect(member?.isEnabled).toBe(true);
  });

  it('findEnabledByApiKey returns null when the api key does not match', async () => {
    const { repo, sqlite } = setup();
    sqlite.prepare(`INSERT INTO weaving_user (usr_api_key, usr_status) VALUES (?, 1)`).run('right');
    expect(await repo.findEnabledByApiKey('wrong')).toBeNull();
  });

  it('findEnabledByApiKey returns null for a disabled member', async () => {
    const { repo, sqlite } = setup();
    sqlite.prepare(`INSERT INTO weaving_user (usr_api_key, usr_status) VALUES (?, 0)`).run('secret');
    expect(await repo.findEnabledByApiKey('secret')).toBeNull();
  });

  it('findById returns the row for the given primary key', async () => {
    const { repo, sqlite } = setup();
    const info = sqlite.prepare(`INSERT INTO weaving_user (usr_user_name, usr_status) VALUES (?, 1)`).run('bob');
    const member = await repo.findById(Number(info.lastInsertRowid));
    expect(member?.username).toBe('bob');
  });

  it('findById returns null for an unknown id', async () => {
    const { repo } = setup();
    expect(await repo.findById(9999)).toBeNull();
  });
});
