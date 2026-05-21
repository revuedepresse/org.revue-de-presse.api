import type Database from 'better-sqlite3';

export function bootstrapSqlite(db: Database.Database): void {
  db.exec(`
    CREATE TABLE IF NOT EXISTS weaving_user (
      usr_id INTEGER PRIMARY KEY AUTOINCREMENT,
      usr_api_key TEXT,
      usr_user_name TEXT,
      usr_username_canonical TEXT,
      usr_status INTEGER NOT NULL DEFAULT 0
    );
  `);
}
