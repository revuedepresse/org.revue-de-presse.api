SHELL:=/bin/bash

.PHONY: help build clean clear-app-cache clear-cache install install-hooks migrate phpstan restart start stop test update-version \
        bench-with-redis bench-without-redis bench-deps \
        chat-jwt-secret chat-embed-snapshots chat-store-setup chat-store-reset \
        chat-cache-clear chat-cron-install chat-cron-uninstall

# Defaults for the highlights perf harness. Override on the command line, e.g.
#   make bench-with-redis BENCH_CONCURRENCY=32 BENCH_ITERATIONS=400
# Exported so fun.sh::run_bench_highlights sees them in its environment.
BENCH_ITERATIONS    ?= 200
BENCH_CONCURRENCY   ?= 1
BENCH_WARMUP        ?= 3
BENCH_TIMEOUT       ?= 30
BENCH_MEMORY_LIMIT  ?= 1G
export BENCH_ITERATIONS BENCH_CONCURRENCY BENCH_WARMUP BENCH_TIMEOUT BENCH_MEMORY_LIMIT

# Pass TAG through to fun.sh::run_update_version. When set, the function
# uses it as both the source for the PHP version literal AND as the
# argument to `git tag`. Example:
#   make update-version TAG=v5.2.0-http-api
TAG ?=
export TAG

COMPOSE_PROJECT_NAME ?= 'org_example_api'
SERVICE ?= 'org.example.api'
TMP_DIR ?= '/tmp/tmp_${SERVICE}'

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: ## Build container image (cache, service)
	@/bin/bash -c 'source fun.sh && build'

clean: ## Remove service container
	@/bin/bash -c 'source fun.sh && clean "${TMP_DIR}"'

clear-app-cache: ## Clear application cache
	@/bin/bash -c 'source fun.sh && clear_cache_warmup'

clear-cache: ## Flush Redis cache
	@/bin/bash -c 'source fun.sh && clear_cache'

install: build ## Install requirements (build → up app → install-app-requirements → clear+warm cache → migrate)
	@/bin/bash -c 'source fun.sh && install'

install-hooks: ## Enable repo-tracked git hooks under .githooks (sets core.hooksPath)
	@git config core.hooksPath .githooks
	@echo "✅ core.hooksPath set to .githooks (pre-commit will run phpstan)."

phpstan: ## Run static analysis (php bin/phpstan analyse) with a 1G memory limit
	@php bin/phpstan analyse --no-progress --memory-limit=1G

migrate: ## Apply pending Doctrine migrations in the running app container (idempotent)
	@/bin/bash -c 'source fun.sh && run_doctrine_migrations'

restart: clear-app-cache stop start ## Restart service

start: ## Start service and key value database
	@/bin/bash -c 'source fun.sh && start'

stop: ## Stop service
	@/bin/bash -c 'source fun.sh && stop'

test: ## Run unit tests with PHPUnit
	@/bin/bash -c 'source fun.sh && run_php_unit_tests'

bench-deps: ## Install composer dev deps via the `app` service container (idempotent)
	@/bin/bash -c 'source fun.sh && run_bench_deps'

bench-with-redis: ## Run the highlights perf harness with Redis cache active (no x-benchmark header)
	@/bin/bash -c 'source fun.sh && run_bench_with_redis'

bench-without-redis: ## Run the highlights perf harness with Redis bypassed (x-benchmark header)
	@/bin/bash -c 'source fun.sh && run_bench_without_redis'

chat-jwt-secret: ## Generate a fresh 256-bit API_JWT_SECRET; print to stdout (does NOT touch any .env)
	@/bin/bash -c 'source fun.sh && run_chat_jwt_secret'

chat-embed-snapshots: ## Embed snapshots into pgvector via `bin/console chat:embed-snapshots $(ARGS)`
	@/bin/bash -c 'source fun.sh && run_chat_embed_snapshots "$(ARGS)"'

chat-store-setup: ## Provision the pgvector publication-embedding store (idempotent — CREATE TABLE IF NOT EXISTS)
	@/bin/bash -c 'source fun.sh && run_chat_store_setup'

chat-store-reset: ## DROP + recreate the pgvector store from current ai.yaml (destructive — use after changing vector_type / vector_size)
	@/bin/bash -c 'source fun.sh && run_chat_store_reset'

chat-cache-clear: ## Wipe the api-service Symfony cache (use after editing ai.yaml / services.chat.yaml — --no-debug doesn't auto-invalidate)
	@/bin/bash -c 'source fun.sh && run_chat_cache_clear'

chat-cron-install: ## Install + enable systemd timer that runs chat:embed-snapshots daily (Linux host w/ systemd; sudo required)
	@/bin/bash -c 'source fun.sh && run_chat_cron_install'

chat-cron-uninstall: ## Disable the timer and remove the systemd units (sudo required)
	@/bin/bash -c 'source fun.sh && run_chat_cron_uninstall'

update-version: ## Sync repo version with latest git tag, OR `make update-version TAG=vX.Y.Z` to set + create a new tag
	@/bin/bash -c 'source fun.sh && run_update_version'
