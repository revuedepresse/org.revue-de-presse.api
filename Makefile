SHELL:=/bin/bash

.PHONY: help build clean install restart start stop

COMPOSE_PROJECT_NAME = ?= 'org_example_api'
SERVICE ?= 'api.example.org'
TMP_DIR ?= '/tmp/tmp_${SERVICE}'

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: ## Build web worker image (PHP FPM)
	@/bin/bash -c 'source fun.sh && build'

clean: ## Remove service container
	@/bin/bash -c 'source fun.sh && clean "${TMP_DIR}"'

clear-cache: ## Clear cache
	@/bin/bash -c 'source fun.sh && clear_cache_warmup'

start-database: ## Start MySQL database
	@/bin/bash -c 'source fun.sh && start_database'

install: ## Install requirements
	@/bin/bash -c 'source fun.sh && install'

restart: clear-cache stop start ## Restart service

test: ## Run unit tests with PHPUnit
	@/bin/bash -c 'source fun.sh && run_php_unit_tests'

start: ## Run PHP-FPM worker
	@/bin/bash -c 'source fun.sh && start'

stop: ## stop service
	@/bin/bash -c 'source fun.sh && stop'
