SHELL := /bin/bash

.PHONY: help install build clean \
        start start-dev \
        lint format typecheck \
        test test-unit test-component test-e2e test-perf test-all \
        db-introspect schema-parity \
        docker-build

# Pass-through env defaults for ad-hoc runs (override on the command line).
APP_ENV            ?= dev
PORT               ?= 3000
DATABASE_URL       ?= sqlite:///:memory:
REDIS_HOST         ?= 127.0.0.1
REDIS_PORT         ?= 6379
ALLOWED_ORIGIN     ?= .*
RATE_LIMIT_ENABLED ?= false
export APP_ENV PORT DATABASE_URL REDIS_HOST REDIS_PORT ALLOWED_ORIGIN RATE_LIMIT_ENABLED

# Perf-harness overrides — mirror the root Makefile so `make test-perf` shares vars.
BENCHMARK_HOST      ?=
API_CLIENT_SECRET   ?=
BENCH_ITERATIONS    ?= 50
BENCH_CONCURRENCY   ?= 1
BENCH_WARMUP        ?= 3
BENCH_TIMEOUT       ?= 30
BENCH_BYPASS_CACHE  ?= 1
BENCH_P95_CEILING_MS ?= 1500
export BENCHMARK_HOST API_CLIENT_SECRET BENCH_ITERATIONS BENCH_CONCURRENCY BENCH_WARMUP BENCH_TIMEOUT BENCH_BYPASS_CACHE BENCH_P95_CEILING_MS

help:
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-22s\033[0m %s\n", $$1, $$2}'

install: ## Install npm dependencies
	@npm ci

build: ## Compile TypeScript to dist/
	@npm run build

clean: ## Remove dist/ and node_modules
	@rm -rf dist node_modules drizzle-introspected

start: build ## Run the compiled app (production-style, requires build)
	@node dist/main.js

start-dev: ## Run the app in watch mode (no build step)
	@npm run start:dev

lint: ## Lint with ESLint (zero warnings)
	@npm run lint

format: ## Check formatting with Prettier (read-only)
	@npm run format

typecheck: ## Type-check with tsc --noEmit
	@npm run typecheck

test: test-unit test-component ## Run unit + component test tiers

test-unit: ## Run unit tests
	@npm run test:unit

test-component: ## Run component tests (Drizzle SQLite, in-memory Redis)
	@npm run test:component

test-e2e: ## Run e2e tests (supertest against booted app)
	@npm run test:e2e

test-perf: ## Run perf harness (skips unless BENCHMARK_HOST and API_CLIENT_SECRET are set)
	@npm run test:perf

test-all: lint typecheck test test-e2e ## Run the full local gate (lint + types + all tests except perf)

db-introspect: ## Introspect live Postgres schema via drizzle-kit (needs DATABASE_URL pointing at PG)
	@npx drizzle-kit introspect

schema-parity: ## Run the Drizzle introspect + drift check against schema.ts
	@npm run db:introspect:check

docker-build: ## Build the production container image from the repo root
	@docker build -f ../provisioning/containers/api-nest/Dockerfile -t revue-de-presse/api-nest:dev ..
