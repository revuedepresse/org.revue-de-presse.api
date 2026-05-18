export const SNAPSHOT_READER = Symbol('SNAPSHOT_READER');

export interface SnapshotReader {
  read(date: string): Promise<unknown[] | Record<string, unknown>>;
}
