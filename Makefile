SHELL:=/bin/bash

.PHONY: help build clean clear-app-cache clear-cache install restart start stop test update-version \
        bench-with-redis bench-without-redis bench-deps reverse-proxy-password \
        php-worker-build php-worker-start php-worker-stop php-worker-logs \
        reverse-proxy-build reverse-proxy-start reverse-proxy-stop \
        start-benchmark-stack stop-benchmark-stack restart-benchmark-stack

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

install: build ## Install requirements
	@/bin/bash -c 'source fun.sh && install'

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

php-worker-build: ## Build the php-worker image (FrankenPHP under the hood; compose profile `frankenphp`)
	@/bin/bash -c 'source fun.sh && run_php_worker_build'

php-worker-start: php-worker-build ## Start the php-worker container, detached (rebuilds the image first)
	@/bin/bash -c 'source fun.sh && run_php_worker_start'

php-worker-stop: ## Stop the php-worker container (kept around for log inspection)
	@/bin/bash -c 'source fun.sh && run_php_worker_stop'

php-worker-logs: ## Tail php-worker logs
	@/bin/bash -c 'source fun.sh && run_php_worker_logs'

reverse-proxy-build: ## Pull the reverse-proxy image (Traefik; compose profile `frankenphp`)
	@/bin/bash -c 'source fun.sh && run_reverse_proxy_build'

reverse-proxy-start: reverse-proxy-build ## Start the reverse-proxy container, detached (pulls fresh image first; depends_on php-worker)
	@/bin/bash -c 'source fun.sh && run_reverse_proxy_start'

reverse-proxy-stop: ## Stop the reverse-proxy container (kept around for log inspection)
	@/bin/bash -c 'source fun.sh && run_reverse_proxy_stop'

reverse-proxy-password: ## Generate a random TRAEFIK_DASHBOARD_USERS line; copy it into .env.local manually
	@/bin/bash -c 'source fun.sh && run_reverse_proxy_password "${TRAEFIK_USER:-admin}"'

start-benchmark-stack: php-worker-start reverse-proxy-start ## Build (if needed) and start the full benchmark stack (php-worker + reverse-proxy)
	@printf '✅ Benchmark stack up — php-worker + reverse-proxy detached.%s' $$'\n'

stop-benchmark-stack: reverse-proxy-stop php-worker-stop ## Stop the full benchmark stack (reverse-proxy first, then php-worker)
	@printf '✅ Benchmark stack stopped — containers retained for log inspection.%s' $$'\n'

restart-benchmark-stack: stop-benchmark-stack start-benchmark-stack ## Stop the benchmark stack and start it back up (rebuilds if needed)
	@printf '✅ Benchmark stack restarted.%s' $$'\n'

update-version: ## Sync repo version with latest git tag, OR `make update-version TAG=vX.Y.Z` to set + create a new tag
	@/bin/bash -c 'source fun.sh && run_update_version'
