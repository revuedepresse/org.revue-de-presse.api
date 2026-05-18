import type { Config } from 'jest';

const config: Config = {
  rootDir: '.',
  testEnvironment: 'node',
  moduleFileExtensions: ['ts', 'js', 'json'],
  transform: { '^.+\\.ts$': ['ts-jest', { tsconfig: 'tsconfig.json' }] },
  testMatch: ['<rootDir>/test/e2e/**/*.e2e-spec.ts', '<rootDir>/test/perf/**/*.perf-spec.ts'],
  moduleNameMapper: { '^@/(.*)$': '<rootDir>/src/$1', '^@test/(.*)$': '<rootDir>/test/$1' },
  setupFiles: ['<rootDir>/test/setup/load-env.ts'],
  testTimeout: 30000,
};
export default config;
