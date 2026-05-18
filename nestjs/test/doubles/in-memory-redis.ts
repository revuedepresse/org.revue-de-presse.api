type Entry = { value: string; expiresAt: number | null };

export class InMemoryRedis {
  private store = new Map<string, Entry>();

  async setex(key: string, ttl: number, value: string): Promise<'OK'> {
    this.store.set(key, { value, expiresAt: Date.now() + ttl * 1000 });
    return 'OK';
  }
  async set(key: string, value: string): Promise<'OK'> {
    this.store.set(key, { value, expiresAt: null });
    return 'OK';
  }
  async get(key: string): Promise<string | null> {
    const entry = this.store.get(key);
    if (!entry) return null;
    if (entry.expiresAt !== null && entry.expiresAt <= Date.now()) {
      this.store.delete(key);
      return null;
    }
    return entry.value;
  }
  async del(key: string): Promise<number> {
    return this.store.delete(key) ? 1 : 0;
  }
  async eval(_script: string, _numKeys: number, ..._args: string[]): Promise<unknown> {
    throw new Error('InMemoryRedis.eval not implemented — override per test if needed');
  }
  async quit(): Promise<'OK'> { return 'OK'; }
  reset(): void { this.store.clear(); }
}
