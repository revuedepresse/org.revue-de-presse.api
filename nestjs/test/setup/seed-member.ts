import type Database from 'better-sqlite3';

export function seedMember(sqlite: Database.Database, apiKey: string, username = 'test-user'): number {
  const info = sqlite.prepare(
    `INSERT INTO weaving_user (usr_api_key, usr_user_name, usr_status) VALUES (?, ?, 1)`,
  ).run(apiKey, username);
  return Number(info.lastInsertRowid);
}
