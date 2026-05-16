SHELL:=/bin/bash

.PHONY: help build clean clear-app-cache clear-cache install restart start stop test update-version \
        bench-with-redis bench-without-redis bench-deps traefik-password

REPOSITORY_VERSION_FILE := src/Trends/Infrastructure/Repository/PopularPublicationRepository.php

# Defaults for the highlights perf harness. Override on the command line, e.g.
#   make bench-with-redis BENCH_CONCURRENCY=32 BENCH_ITERATIONS=400
# Exported so fun.sh::run_bench_highlights sees them in its environment.
BENCH_ITERATIONS    ?= 200
BENCH_CONCURRENCY   ?= 1
BENCH_WARMUP        ?= 3
BENCH_TIMEOUT       ?= 30
BENCH_MEMORY_LIMIT  ?= 1G
export BENCH_ITERATIONS BENCH_CONCURRENCY BENCH_WARMUP BENCH_TIMEOUT BENCH_MEMORY_LIMIT

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

traefik-password: ## Generate a random TRAEFIK_DASHBOARD_USERS line; copy it into .env.local manually
	@/bin/bash -c 'source fun.sh && run_traefik_password "${TRAEFIK_USER:-admin}"'

update-version: ## Sync hard-coded repository version with the latest git tag (idempotent)
	@latest=$$(git describe --tags --abbrev=0 | sed -E 's/^(v[0-9]+(\.[0-9]+){1,2}).*/\1/'); \
	if [ -z "$$latest" ]; then echo "ERROR: no git tag found"; exit 1; fi; \
	current=$$(sed -nE "s/^[[:space:]]+'version' => '([^']+)',?\$$/\1/p" $(REPOSITORY_VERSION_FILE) | head -1); \
	if [ -z "$$current" ]; then echo "ERROR: 'version' key not found in $(REPOSITORY_VERSION_FILE)"; exit 1; fi; \
	if [ "$$current" = "$$latest" ]; then \
		echo "$(REPOSITORY_VERSION_FILE) version already up-to-date: $$current"; \
	else \
		sed -i.bak -E "s|('version' => ')[^']+(',)|\1$$latest\2|" $(REPOSITORY_VERSION_FILE); \
		rm -f $(REPOSITORY_VERSION_FILE).bak; \
		echo "$(REPOSITORY_VERSION_FILE) version updated: $$current -> $$latest"; \
	fi
