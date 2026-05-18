import * as fs from 'node:fs/promises';
import * as path from 'node:path';
import { Inject, Injectable, Logger } from '@nestjs/common';
import { ENV } from '@/config/env';
import type { Env } from '@/config/env';
import { SnapshotReader } from './snapshot-reader';

@Injectable()
export class FilesystemSnapshotReader implements SnapshotReader {
  private readonly logger = new Logger(FilesystemSnapshotReader.name);
  private readonly projectDir: string;

  constructor(@Inject(ENV) envOrDir: Env | string) {
    this.projectDir = typeof envOrDir === 'string' ? envOrDir : (envOrDir.PROJECT_DIR ?? process.cwd());
  }

  async read(date: string): Promise<unknown[] | Record<string, unknown>> {
    const file = path.join(this.projectDir, 'src/Bluesky/Resources', `${date}.json`);
    let raw: string;
    try {
      raw = await fs.readFile(file, 'utf8');
    } catch {
      this.logger.log({ msg: 'snapshot missing', date });
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
