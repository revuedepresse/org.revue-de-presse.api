import * as fs from 'node:fs/promises';
import * as path from 'node:path';
import type { Env } from '@/config/env';
import { SnapshotReader } from './snapshot-reader';
import { Logger, NoopLogger } from '@/core/ports/logger';

export class FilesystemSnapshotReader implements SnapshotReader {
  private readonly projectDir: string;
  private readonly logger: Logger;

  constructor(envOrDir: Env | string, logger: Logger = new NoopLogger()) {
    this.projectDir = typeof envOrDir === 'string' ? envOrDir : (envOrDir.PROJECT_DIR ?? process.cwd());
    this.logger = logger;
  }

  async read(date: string): Promise<unknown[] | Record<string, unknown>> {
    const file = path.join(this.projectDir, 'src/Bluesky/Resources', `${date}.json`);
    let raw: string;
    try {
      raw = await fs.readFile(file, 'utf8');
    } catch {
      this.logger.log({ msg: 'snapshot missing', date }, 'FilesystemSnapshotReader');
      return [];
    }
    try {
      const decoded = JSON.parse(raw);
      if (Array.isArray(decoded) || (decoded && typeof decoded === 'object')) return decoded;
      return [];
    } catch {
      return [];
    }
  }
}
