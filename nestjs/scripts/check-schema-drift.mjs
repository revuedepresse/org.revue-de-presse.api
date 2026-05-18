import { readFileSync, existsSync } from 'node:fs';

const INTROSPECTED = './drizzle-introspected/schema.ts';
if (!existsSync(INTROSPECTED)) {
  console.error('drizzle-introspected/schema.ts not found — did drizzle-kit introspect run?');
  process.exit(2);
}

const live = readFileSync(INTROSPECTED, 'utf8');

const expectedColumns = [
  'usr_id', 'usr_api_key', 'usr_user_name', 'usr_username_canonical', 'usr_status',
];
for (const col of expectedColumns) {
  if (!live.includes(col)) {
    console.error(`drift: column ${col} missing from live schema`);
    process.exit(1);
  }
}
console.log('schema-parity OK');
