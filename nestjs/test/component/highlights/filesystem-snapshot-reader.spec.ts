import * as fs from 'node:fs';
import * as os from 'node:os';
import * as path from 'node:path';
import { FilesystemSnapshotReader } from '@/highlights/filesystem-snapshot-reader';

describe('FilesystemSnapshotReader', () => {
  let projectDir: string;

  beforeEach(() => {
    projectDir = fs.mkdtempSync(path.join(os.tmpdir(), 'snapshot-reader-'));
    fs.mkdirSync(path.join(projectDir, 'src/Bluesky/Resources'), { recursive: true });
  });
  afterEach(() => fs.rmSync(projectDir, { recursive: true, force: true }));

  it('returns decoded JSON for an existing date', async () => {
    const payload = [{ id: 'abc', title: 'first highlight' }];
    fs.writeFileSync(path.join(projectDir, 'src/Bluesky/Resources/2026-05-02.json'), JSON.stringify(payload));
    const reader = new FilesystemSnapshotReader(projectDir);
    expect(await reader.read('2026-05-02')).toEqual(payload);
  });

  it('returns empty array for a missing date', async () => {
    const reader = new FilesystemSnapshotReader(projectDir);
    expect(await reader.read('1999-01-01')).toEqual([]);
  });

  it('returns empty array for malformed JSON', async () => {
    fs.writeFileSync(path.join(projectDir, 'src/Bluesky/Resources/2026-05-02.json'), '{ not json');
    const reader = new FilesystemSnapshotReader(projectDir);
    expect(await reader.read('2026-05-02')).toEqual([]);
  });
});
