export class PerformanceMetrics {
  private readonly sorted: number[];

  constructor(private readonly samplesMs: number[], private readonly errorCount: number = 0) {
    this.sorted = [...samplesMs].sort((a, b) => a - b);
  }

  count(): number { return this.samplesMs.length; }
  errors(): number { return this.errorCount; }
  min(): number { return this.sorted[0] ?? 0; }
  max(): number { return this.sorted[this.sorted.length - 1] ?? 0; }
  mean(): number {
    if (this.samplesMs.length === 0) return 0;
    return this.samplesMs.reduce((a, b) => a + b, 0) / this.samplesMs.length;
  }
  p50(): number { return this.percentile(0.5); }
  p95(): number { return this.percentile(0.95); }
  p99(): number { return this.percentile(0.99); }

  throughput(wallClockSeconds: number): number {
    if (wallClockSeconds <= 0) return 0;
    return this.count() / wallClockSeconds;
  }

  private percentile(rank: number): number {
    const n = this.sorted.length;
    if (n === 0) return 0;
    let idx = Math.ceil(rank * n) - 1;
    if (idx < 0) idx = 0;
    if (idx >= n) idx = n - 1;
    return this.sorted[idx];
  }
}
