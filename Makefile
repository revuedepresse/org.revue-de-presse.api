SHELL:=/bin/bash

.PHONY: doc build clean help install restart start stop test

.PHONY: clear-app-cache

.PHONY: consume-fetch-publication-messages dispatch-amqp-messages

.PHONY: purge-amqp-queue set-up-amqp-queues

.PHONY: shell-process-manager shell-worker

.PHONY: start-database stop-database test

COMPOSE_PROJECT_NAME ?= 'org_example_worker'
WORKER ?= 'org.example.worker'
TMP_DIR ?= '/tmp/tmp_${WORKER}'

help: doc
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: ## Build worker image
	@/bin/bash -c 'source fun.sh && build'

clean: ## Remove worker container
	@/bin/bash -c 'source fun.sh && clean "${TMP_DIR}"'

clear-app-cache: ## Clear application cache
	@/bin/bash -c 'source fun.sh && clear_cache_warmup'

dispatch-amqp-messages: ## Dispatch AMQP Fetch publications messages
	@/bin/bash -c 'source fun.sh && dispatch_amqp_messages'

consume-fetch-publication-messages: ## Consume AMQP Fetch publication messages
	@/bin/bash -c 'source ./bin/console.sh && bin/consume-fetch-publication-messages.sh'

doc:
	@command -v bat && bat ./doc/commands.md || cat ./doc/commands.md

install: build ## Install requirements
	@/bin/bash -c 'source fun.sh && install'

list-amqp-messages: ## List AMQP messags
	@/bin/bash -c 'source ./bin/console.sh && list_amqp_queues'

purge-amqp-queue: ## Purge queue
	@/bin/bash -c 'source ./bin/console.sh && purge_queues'

restart: clear-app-cache stop start ## Restart worker

shell-process-manager: ## Get shell in process manager container
	@/bin/bash -c 'source fun.sh && get_process_manager_shell'

shell-worker: ## Get shell in worker container
	@/bin/bash -c 'source fun.sh && get_worker_shell'

start: ## Run worker
	@/bin/bash -c 'source fun.sh && start'

start-amqp-broker: ## Start AMQP broker
	@/bin/bash -c 'source fun.sh && start_amqp_broker'

set-up-amqp-queues: ## Set up AMQP queues
	@/bin/bash -c 'source ./bin/console.sh && set_up_amqp_queues'

start-database: ## Start database
	@/bin/bash -c 'source fun.sh && start_database'

stop: ## Stop worker
	@/bin/bash -c 'source fun.sh && stop'

stop-amqp-broker: ## Stop AMQP broker
	@/bin/bash -c 'source fun.sh && stop_amqp_broker'

stop-database: ## Stop database
	@/bin/bash -c 'source fun.sh && stop_database'

test: ## Run unit tests
	@/bin/bash -c 'source fun.sh && run_unit_tests'
