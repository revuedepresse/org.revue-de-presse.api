import { boolean, integer, pgTable, varchar } from 'drizzle-orm/pg-core';
import {
  integer as sInt,
  sqliteTable,
  text,
} from 'drizzle-orm/sqlite-core';

export const weavingUserPg = pgTable('weaving_user', {
  id: integer('usr_id').primaryKey().generatedAlwaysAsIdentity(),
  apiKey: varchar('usr_api_key', { length: 255 }),
  username: varchar('usr_user_name', { length: 255 }),
  usernameCanonical: varchar('usr_username_canonical', { length: 255 }),
  enabled: boolean('usr_status').notNull().default(false),
});

export const weavingUserSqlite = sqliteTable('weaving_user', {
  id: sInt('usr_id').primaryKey({ autoIncrement: true }),
  apiKey: text('usr_api_key'),
  username: text('usr_user_name'),
  usernameCanonical: text('usr_username_canonical'),
  enabled: sInt('usr_status', { mode: 'boolean' }).notNull().default(false),
});

export const weavingUser = (process.env.APP_ENV === 'test' ? weavingUserSqlite : weavingUserPg) as
  typeof weavingUserPg;
