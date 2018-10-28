SHELL:=/bin/bash

## See also https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html

.PHONY: help

help:
		@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

create-network: ## Create Docker network
		@/bin/bash -c 'source ./bin/functions.sh && create_network'

remove-mysql-container: ## Remove MySQL container
		@/bin/bash -c 'source ./bin/functions.sh && remove_mysql_container'

build-apache-container: ## Build Apache image
		@/bin/bash -c 'source ./bin/functions.sh && build_apache_container'

set-permissions-in-apache-container: # Set permissions in Apache container
		@/bin/bash -c 'source ./bin/functions.sh && set_permissions_in_apache_container'

get-apache-interactive-shell: ## Get Apache interactive shell
		@/bin/bash -c 'source ./bin/functions.sh && get_apache_container_interactive_shell'

remove-apache-container: ## Remove Apache container
		@/bin/bash -c 'source ./bin/functions.sh && remove_apache_container'

run-apache-container: ## Run Apache container
		@/bin/bash -c 'source ./bin/functions.sh && run_apache'

run-mysql-container: ## Run MySQL container (https://hub.docker.com/_/mysql/)
		@/bin/bash -c 'source ./bin/functions.sh && run_mysql_container'

run-mysql-client: ## Run MySQL client
		@/bin/bash -c 'source ./bin/functions.sh && run_mysql_client'

initialize-mysql-volume: ## Set up MySQL container
		@/bin/bash -c 'source ./bin/functions.sh && initialize_mysql_volume'

grant-privileges: ## Grant privileges
		@/bin/bash -c 'source ./bin/functions.sh && grant_privileges'

build-php-container: ## Build PHP image
		@/bin/bash -c 'source ./bin/functions.sh && build_php_container'

list-php-extensions: ## List PHP extensions
		@/bin/bash -c 'source ./bin/functions.sh && list_php_extensions'

remove-php-container: ## Remove PHP container
		@/bin/bash -c 'source ./bin/functions.sh && remove_php_container'

run-php-script: ## Run PHP script
		@/bin/bash -c 'source ./bin/functions.sh && run_php_script ${1}'

install-php-dependencies: ## Install PHP dependencies
		@/bin/bash -c 'source ./bin/functions.sh && install_php_dependencies'

run-php: ## Run PHP with arguments
		@/bin/bash -c 'source ./bin/functions.sh && run_php'

build-php-fpm-container: ## Build PHP-FPM image
		@/bin/bash -c 'source ./bin/functions.sh && build_php_fpm_container'

run-php-fpm: ## Run PHP-FPM worker
		@/bin/bash -c 'source ./bin/functions.sh && run_php_fpm'

remove-php-fpm-container: ## Remove PHP-FPM container
		@/bin/bash -c 'source ./bin/functions.sh && remove_php_fpm_container'

create-database-schema-test: # Create database schema in test environment
		@/bin/bash -c 'source ./bin/functions.sh && create_database_test_schema'

diff-schema: ## Generate schema migrations scripts
		@/bin/bash -c "export PROJECT_DIR=`pwd`; echo 'php /var/www/devobs/app/console doc:mig:diff -vvvv' | make run-php"

migrate-schema-in-production: ## Migrate the database schema in production
		@/bin/bash -c 'export PROJECT_DIR='/var/www/devobs'; source ./bin/functions.sh && migrate_schema'

configure-rabbitmq-user-privileges: ## Configure RabbitMQ user privileges
		@/bin/bash -c 'source ./bin/functions.sh && configure_rabbitmq_user_privileges'

setup-amqp-fabric: ## Set up AMQP fabric (create expected queue)
		@/bin/bash -c 'echo "php "${PROJECT_DIR}"/app/console rabbitmq:setup-fabric" | make run-php'

list-amqp-messages: ## List AMQP messags
		@/bin/bash -c 'source ./bin/functions.sh && list_amqp_queues'

purge-amqp-queue: ## Purge queue
		@/bin/bash -c 'source ./bin/functions.sh && purge_queues'

run-rabbitmq-container: ## Run RabbitMQ container (https://hub.docker.com/_/rabbitmq/)
		@/bin/bash -c 'source ./bin/functions.sh && run_rabbitmq_container'

remove-rabbitmq-container: ## Remove RabbitMQ container
		@/bin/bash -c 'source ./bin/functions.sh && remove_rabbitmq_container'

list-rabbitmq-messages: ## List messages accumulated with RabbitMQ
		@/bin/bash -c '/usr/local/sbin/rabbitmqctl list_queues -p /weaving_the_web'

produce-amqp-messages-from-members-lists: ## Produce messages from members lists
		@/bin/bash -c 'source ./bin/functions.sh && produce_amqp_messages_from_members_lists'

produce-amqp-messages-from-aggregates-lists: ## Produce messages from aggregates list
		@/bin/bash -c 'source ./bin/functions.sh && produce_amqp_messages_for_aggregates_list'

produce-amqp-messages-from-search-query: ## Produce messages from search query
		@/bin/bash -c 'source ./bin/functions.sh && produce_amqp_messages_for_search_query'

produce-amqp-messages-for-timely-statuses: ## Produce messages for timely statuses
		@/bin/bash -c 'source ./bin/functions.sh && produce_amqp_messages_for_timely_statuses'

produce-amqp-messages-for-networks: ## Produce messages for networks
		@/bin/bash -c 'source ./bin/functions.sh && produce_amqp_messages_for_networks'

produce-amqp-messages-from-news-lists: ## Produce messages from news list
		@/bin/bash -c 'source ./bin/functions.sh && produce_amqp_messages_for_news_list'

produce-amqp-messages-from-member-timeline: ## Produce messages from member timeline
		@/bin/bash -c 'source ./bin/functions.sh && produce_amqp_messages_from_member_timeline'

refresh-statuses: ## Refresh statuses
		@/bin/bash -c 'source ./bin/functions.sh && refresh_statuses'

keep-php-container-running: ## Keep a running container having PHP
		@/bin/bash -c 'source ./bin/functions.sh && keep_php_container_running'

consume-twitter-api-messages: ## Consume twitter API messages
		@/bin/bash -c 'export PROJECT_DIR=`pwd` DOCKER_MODE=1 && cd "${PROJECT_DIR}" && source bin/consume_twitter_api.sh'

consume-twitter-api-news-messages: ## Consume twitter API news messages
		@/bin/bash -c 'export PROJECT_DIR=`pwd` DOCKER_MODE=1 && cd "${PROJECT_DIR}" && source bin/consume_twitter_api_for_news.sh'

today-statuses: ## Filter the statuses for today from the log file
		@/bin/bash -c 'source ./bin/functions.sh && today_statuses'

follow-today-statuses: ## Filter the statuses for today from the log file
		@/bin/bash -c 'source ./bin/functions.sh && follow_today_statuses'

run-php-unit-tests: ## Run unit tests with PHPUnit
		@/bin/bash -c 'source ./bin/functions.sh && run_php_unit_tests'
