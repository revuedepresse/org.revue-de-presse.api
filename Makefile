SHELL:=/bin/bash

.PHONY: help build clean clear-app-cache install restart start start-database stop test

WORKER ?= 'worker.example.org'
COMPOSE_PROJECT_NAME ?= ''
TMP_DIR ?= '/tmp/tmp_${WORKER}'

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: ## Build worker image
	@/bin/bash -c 'source fun.sh && build'

clean: ## Remove worker container
	@/bin/bash -c 'source fun.sh && clean "${TMP_DIR}"'

clear-app-cache: ## Clear application cache
	@/bin/bash -c 'source fun.sh && clear_cache_warmup'

install: ## Install requirements
	@/bin/bash -c 'source fun.sh && install'

restart: clear-app-cache stop start ## Restart worker

start: ## Run worker
	@/bin/bash -c 'source fun.sh && start'

start-database: ## Start database
	@/bin/bash -c 'source fun.sh && start_database'

stop: ## Stop worker
	@/bin/bash -c 'source fun.sh && stop'

test: ## Run unit tests
	@/bin/bash -c 'source fun.sh && run_unit_tests'
