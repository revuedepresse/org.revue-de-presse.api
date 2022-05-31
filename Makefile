SHELL:=/bin/bash

.PHONY: help build clean install restart start stop

TMP_DIR ?= /tmp/tmp_revue-de-presse.org

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: ## Build web worker image (PHP FPM)
	@/bin/bash -c 'source ./fun.sh && build'

clean: ## Remove service container
	@/bin/bash -c 'source ./fun.sh && clean "${TMP_DIR}"'

start-database: ## Start MySQL database
	@/bin/bash -c 'source ./fun.sh && start_database'

install: ## Install requirements
	@/bin/bash -c 'source fun.sh && install'

restart: stop start ## Restart service

test: ## Run unit tests with PHPUnit
	@/bin/bash -c 'source ./fun.sh && run_php_unit_tests'

start: ## Run PHP-FPM worker
	@/bin/bash -c 'source ./fun.sh && start'

stop: ## stop service
	@/bin/bash -c 'source fun.sh && stop'
