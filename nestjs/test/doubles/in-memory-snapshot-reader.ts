import { SnapshotReader } from '@/core/highlights/snapshot-reader';

export class InMemorySnapshotReader implements SnapshotReader {
  constructor(private snapshotsByDate: Record<string, unknown[] | Record<string, unknown>> = {}) {}
  async read(date: string) { return this.snapshotsByDate[date] ?? []; }
}
