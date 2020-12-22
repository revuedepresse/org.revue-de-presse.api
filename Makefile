SHELL:=/bin/bash

## See also https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html

.PHONY: help

help:
		@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

create-network: ## Create Docker network
		@/bin/bash -c 'source ./bin/console.sh && create_network'

build-stack-images: ## Build Docker images required by development stack
		@/bin/bash -c 'source ./bin/console.sh && build_stack_images'

dispatch-fetch-publications-messages: ## Produce messages to fetch publications
		@/bin/bash -c 'source ./bin/console.sh && dispatch_fetch_publications_messages'

consume-fetch-publication-messages: ## Consume Fetch publication messages
		@/bin/bash -c 'export PROJECT_DIR=`pwd` && cd "${PROJECT_DIR}" && source bin/consume_fetch_publication_messages.sh'

install-php-dependencies: ## Install PHP dependencies (APP_ENV=prod)
		@/bin/bash -c 'source ./bin/console.sh && install_php_dependencies'

run-php: ## Run PHP with arguments
		@/bin/bash -c 'source ./bin/console.sh && run_php'

run-php-script: ## Run PHP script
		@/bin/bash -c 'source ./bin/console.sh && run_php_script'

run-stack: ## Run stack and its dependencies
		@/bin/bash -c 'source ./bin/console.sh && run_stack'

run-worker: ## Run worker and its dependencies
		@/bin/bash -c 'source ./bin/console.sh && run_worker'

list-amqp-messages: ## List AMQP messags
		@/bin/bash -c 'source ./bin/console.sh && list_amqp_queues'

purge-amqp-queue: ## Purge queue
		@/bin/bash -c 'source ./bin/console.sh && purge_queues'

stop-workers: ## Stop workers
		@/bin/bash -c 'source ./bin/console.sh && stop_workers'

run-php-unit-tests: ## Run unit tests with PHPUnit
		@/bin/bash -c 'source ./bin/console.sh && run_php_unit_tests'

run-php-features-tests: ## Run features tests with Behat
		@/bin/bash -c 'source ./bin/console.sh && run_php_features_tests'

restart-web-server: ## Restart web Server
		@/bin/bash -c 'source ./bin/console.sh && restart_web_server'

install-local-ca-store: ## Install local CA in the system trust store
		@/bin/bash -c 'source ./bin/console.sh && install_local_ca_store'

generate-development-tls-certificate-and-key: ## Generate TLS certificate and key for development
		@/bin/bash -c 'source ./bin/console.sh && generate_development_tls_certificate_and_key'

create-test-database: ## Create test database
		@/bin/bash -c 'source ./bin/console.sh && create_test_database'
