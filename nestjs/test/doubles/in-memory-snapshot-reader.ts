import { SnapshotReader } from '@/highlights/snapshot-reader';

export class InMemorySnapshotReader implements SnapshotReader {
  constructor(private snapshotsByDate: Record<string, unknown[] | Record<string, unknown>> = {}) {}
  async read(date: string) { return this.snapshotsByDate[date] ?? []; }
}
