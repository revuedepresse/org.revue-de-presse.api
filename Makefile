SHELL:=/bin/bash

.PHONY: help build clean clear-app-cache consume-fetch-publication-messages dispatch-fetch-publications-messages install purge-amqp-queue restart set-up-amqp-queues start start-database stop stop-database test

WORKER ?= 'worker.example.org'
TMP_DIR ?= '/tmp/tmp_${WORKER}'

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: ## Build worker image
	@/bin/bash -c 'source fun.sh && build'

clean: ## Remove worker container
	@/bin/bash -c 'source fun.sh && clean "${TMP_DIR}"'

clear-app-cache: ## Clear application cache
	@/bin/bash -c 'source fun.sh && clear_cache_warmup'

consume-fetch-publication-messages: ## Consume AMQP Fetch publication messages
	@/bin/bash -c 'source ./bin/console.sh && bin/consume_fetch_publication_messages.sh'

dispatch-fetch-publications-messages: ## Dispatch AMQP Fetch publications messages
	@/bin/bash -c 'source ./bin/console.sh && dispatch_fetch_publications_messages'

install: build ## Install requirements
	@/bin/bash -c 'source fun.sh && install'

list-amqp-messages: ## List AMQP messags
		@/bin/bash -c 'source ./bin/console.sh && list_amqp_queues'

purge-amqp-queue: ## Purge queue
		@/bin/bash -c 'source ./bin/console.sh && purge_queues'

restart: clear-app-cache stop start ## Restart worker

start: ## Run worker
	@/bin/bash -c 'source fun.sh && start'

set-up-amqp-queues: ## Set up AMQP queues
		@/bin/bash -c 'source ./.env.local && source ./bin/console.sh && set_up_amqp_queues'

start-database: ## Start database
	@/bin/bash -c 'source fun.sh && start_database'

stop: ## Stop worker
	@/bin/bash -c 'source fun.sh && stop'

stop-database: ## Stop database
	@/bin/bash -c 'source fun.sh && stop_database'

test: ## Run unit tests
	@/bin/bash -c 'source fun.sh && run_unit_tests'
