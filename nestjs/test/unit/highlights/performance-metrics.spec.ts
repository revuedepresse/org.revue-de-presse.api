import { PerformanceMetrics } from '@/core/highlights/performance-metrics';

describe('PerformanceMetrics', () => {
  it('computes basic statistics on ten samples', () => {
    const m = new PerformanceMetrics([10, 20, 30, 40, 50, 60, 70, 80, 90, 100], 2);
    expect(m.count()).toBe(10);
    expect(m.errors()).toBe(2);
    expect(m.min()).toBe(10);
    expect(m.max()).toBe(100);
    expect(m.mean()).toBe(55);
    expect(m.p50()).toBe(50);
    expect(m.p95()).toBe(100);
    expect(m.p99()).toBe(100);
  });

  it('handles single sample', () => {
    const m = new PerformanceMetrics([42]);
    expect(m.count()).toBe(1);
    expect(m.min()).toBe(42);
    expect(m.max()).toBe(42);
    expect(m.p50()).toBe(42);
    expect(m.p99()).toBe(42);
    expect(m.mean()).toBe(42);
  });

  it('unsorted input does not affect results', () => {
    const m = new PerformanceMetrics([50, 10, 30, 20, 40]);
    expect(m.min()).toBe(10);
    expect(m.max()).toBe(50);
    expect(m.mean()).toBe(30);
    expect(m.p50()).toBe(30);
  });

  it('throughput uses count and wall clock', () => {
    const m = new PerformanceMetrics([1, 1, 1, 1]);
    expect(m.throughput(2)).toBe(2);
    expect(m.throughput(0)).toBe(0);
  });

  it('empty samples returns zero sentinels', () => {
    const m = new PerformanceMetrics([], 5);
    expect(m.count()).toBe(0);
    expect(m.errors()).toBe(5);
    expect(m.min()).toBe(0);
    expect(m.max()).toBe(0);
    expect(m.mean()).toBe(0);
    expect(m.p50()).toBe(0);
    expect(m.p95()).toBe(0);
    expect(m.p99()).toBe(0);
    expect(m.throughput(1)).toBe(0);
  });
});
