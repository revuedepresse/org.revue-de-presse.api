import type { Config } from 'jest';

const config: Config = {
  rootDir: '.',
  testEnvironment: 'node',
  moduleFileExtensions: ['ts', 'js', 'json'],
  transform: { '^.+\\.ts$': ['ts-jest', { tsconfig: 'tsconfig.json' }] },
  testMatch: ['<rootDir>/test/**/*.spec.ts'],
  testPathIgnorePatterns: ['/node_modules/', '/dist/', '/test/e2e/', '/test/perf/'],
  moduleNameMapper: { '^@/(.*)$': '<rootDir>/src/$1', '^@test/(.*)$': '<rootDir>/test/$1' },
  setupFiles: ['<rootDir>/test/setup/load-env.ts'],
};
export default config;
