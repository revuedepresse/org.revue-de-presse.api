#!/usr/bin/env bash

#
# 2022-06-11 - Clean up
#
# 2019-11-10 - Notes about dependencies addition or removal
#
# ```
# export VENDOR_NAME='symfony/symfony:^3.4.x' && make add-php-dependency
# export VENDOR_NAME='symfony/symfony' && make remove-php-dependency
# ```
#
# 2019-10-19 - Notes about clearing application cache
#
# ```
# make clear-backend-application-cache
# ```
#
# 2019-10-06 - Notes about running Apache container
#
# ```
# make build-php-container
# make build-apache-container
#
# # RabbitMQ startup has to be complete
# # when generating cached application configuration
# make run-rabbitmq
#
# # Install PHP vendors
# make install-php-dependencies
#
# make run-apache-container
#
# # Set ACL
# make set-acl
# ```
#
# 2019-06-27 - Notes
#
# - Ensure environment variable PROJECT_DIR has been declared
# - Ensure the appropriate docker network has been created before considering to initialize a MySQL volume
# - Building a PHP docker image is a requirement of the initialization of the MySQL volume
# ```
# # First shortcut command to be executed before running a MySQL container
# make initialize-mysql-volume
# ```
# - Building RabbitMQ docker image would prevent having warning provided the project configuration
# when initializing MySQL volume
# - Before running a RabbitMQ container, the following commands should have been executed
# ```
# make run-rabbitmq-container
# make configure-rabbitmq-user-privileges
# make setup-amqp-fabric
# ```

function consume_fetch_publication_messages {
    local command_suffix
    command_suffix="${1}"

    if [ -z "${command_suffix}" ];
    then
      command_suffix='publications'
    fi

    local namespace
    namespace="${2}"

    if [ -z "${namespace}" ];
    then
        namespace='twitter'
    fi

    export NAMESPACE="handle_amqp_messages_${command_suffix}_${namespace}"

    export XDEBUG_CONFIG="idekey='phpstorm-xdebug'"

    if [ -z "${MESSAGES}" ]
    then
        MESSAGES=10;
        echo '[default count of messages] '$MESSAGES
    fi

    local default_memory_limit
    default_memory_limit='128M'
    if [ -z "${MEMORY_LIMIT}" ]
    then
        MEMORY_LIMIT="${default_memory_limit}"
        echo '[default memory limit] '$MEMORY_LIMIT
    fi

    if [ -z "${TIME_LIMIT}" ]
    then
        TIME_LIMIT="300";
    fi

    if [ -z "${PROJECT_DIR}" ];
    then
        export PROJECT_DIR="/var/www/${WORKER}"
    fi

    local rabbitmq_output_log
    rabbitmq_output_log="./var/log/rabbitmq.out.log"

    local rabbitmq_error_log
    rabbitmq_error_log="./var/log/rabbitmq.error.log"

    ensure_log_files_exist "${rabbitmq_output_log}" "${rabbitmq_error_log}"
    rabbitmq_output_log="${PROJECT_DIR}/${rabbitmq_output_log}"
    rabbitmq_error_log="${PROJECT_DIR}/${rabbitmq_error_log}"

    local php_directives
    php_directives=''

    if [ "${MEMORY_LIMIT}" != '128M' ];
    then
        php_directives='php -dmemory_limit='"${MEMORY_LIMIT}"' '
    fi

    trap stop_workers SIGINT SIGTERM

    local script
    script="${php_directives}bin/console messenger:consume --time-limit=${TIME_LIMIT} -m ${MEMORY_LIMIT} -l ${MESSAGES} "${command_suffix}

    echo 'About to run command: '
    echo "${script}"

    echo 'Redirecting standard output to "'"${rabbitmq_output_log}"'"'
    echo 'Redirecting standard error in "'"${rabbitmq_error_log}"'"'

    /bin/bash -c "$command >> ${rabbitmq_output_log} 2>> ${rabbitmq_error_log}"
}

function dispatch_fetch_publications_messages {
    if [ -z "${NAMESPACE}" ];
    then

        export NAMESPACE="produce_news_messages"

    fi

    if [ -z "${USERNAME}" ];
    then

        printf 'A %s is expected as %s ("%s" environment variable).%s' 'non-empty' 'username' 'USERNAME' $'\n'

        return 1

    fi

    local priority_option
    priority_option=''

    if [ -n "${in_priority}" ];
    then

        priority_option=' --priority_to_aggregates '

    fi

    local query_restriction
    query_restriction=''

    if [ -n "${QUERY_RESTRICTION}" ];
    then

        query_restriction=' --query_restriction='"${QUERY_RESTRICTION}"

    fi

    if [ -z "${LIST_NAME}" ] && [ -z "${MULTIPLE_LISTS}" ];
    then

        printf 'A %s is expected as %s ("%s" environment variable).%s' 'non-empty string' 'a list' 'LIST_NAME' $'\n' 1>&2
        printf 'or%s' $'\n'  1>&2
        printf 'A %s is expected as %s ("%s" environment variable).%s' 'non-empty string' 'comma-separated lists' 'MULTIPLE_LISTS' $'\n' 1>&2

        return 1;

    fi

    local list_option
    list_option=' --list='"'${LIST_NAME}'"

    if [ -n "${MULTIPLE_LISTS}" ];
    then

        list_option=' --lists='"'${MULTIPLE_LISTS}'"

    fi

    local cursor_argument
    cursor_argument=''

    if [ -n "${CURSOR}" ];
    then
        cursor_argument=' --cursor='"${CURSOR}"
    fi

    local arguments
    arguments="${priority_option}${list_option}${query_restriction} ${USERNAME}"
    arguments="${arguments}${cursor_argument}"

    local cmd
    cmd="bin/console devobs:dispatch-messages-to-fetch-member-statuses${arguments}"

    if [ -n "${DRY_MODE}" ];
    then
        printf '%s%s' 'About to run command:' $'\n' 1>&2
        printf '%s%s' '"'"${cmd}"'"' $'\n' 1>&2
    else
        printf '%s%s' "${cmd}" $'\n'

    fi
}

function ensure_log_files_exist() {
    local standard_output_file="${1}"
    local standard_error_file="${2}"

    if [ ! -e ./composer.lock ];
    then

      echo 'Inconsistent file system location prevents executing the next commands'

      return 1

    fi

    if [ ! -e "${standard_output_file}" ];
    then
        sudo touch "${standard_output_file}";
    fi

    if [ ! -e "${standard_error_file}" ];
    then
        sudo touch "${standard_error_file}";
    fi
}

function get_project_dir {
    local project_dir="/var/www/${WORKER}"

    if [ -n "${PROJECT_DIR}" ];
    then
        project_dir="${PROJECT_DIR}"
    fi

    echo "${project_dir}"
}

function get_project_name() {
    local project_name
    project_name='wildcard'

    if [ -n "${PROJECT_NAME}" ]; then
        project_name="${PROJECT_NAME}"
    fi

    if [ -z "${project_name}" ]; then

        printf 'A %s is expected (%s).%s' 'non-empty string' 'project name' $'\n'

        exit 1

    fi

    echo "${project_name}"
}

function get_rabbitmq_virtual_host() {
    local virtual_host
    virtual_host="$(cat <(cat '.env.local' | grep "PUBLICATIONS='amqp" | sed -E 's#.+(/.+)/[^/]*$#\1#' | sed -E 's/\/%2f/\//g'))"

    echo "${virtual_host}"
}

function get_symfony_environment() {
    local symfony_env='dev'
    if [ -n "${SYMFONY_ENV}" ];
    then
        symfony_env="${SYMFONY_ENV}"
    fi

    echo 'APP_ENV='"${symfony_env}"
}

function list_amqp_queues() {
    local rabbitmq_vhost
    rabbitmq_vhost="$(get_rabbitmq_virtual_host)"

    cd provisioning/containers || exit

    local project_files
    project_files='-f ./docker-compose.yaml -f ./docker-compose.override.yaml'

    /bin/bash -c "docker compose ${project_files} exec amqp watch -n1 'rabbitmqctl list_queues -p ${rabbitmq_vhost}'"
}

function load_production_fixtures() {
  local script
  script="php bin/console devobs:load-production-fixtures \
    ${API_TWITTER_USER_TOKEN} \
    ${API_TWITTER_USER_SECRET} \
    ${API_TWITTER_CONSUMER_KEY} \
    ${API_TWITTER_CONSUMER_SECRET} \
    -vvvv"

  run_php_script "${script}" 'interactive_mode'
}

function purge_queues() {
    local rabbitmq_vhost
    rabbitmq_vhost="$(get_rabbitmq_virtual_host)"

    cd provisioning/containers || exit

    local project_files
    project_files='-f ./docker-compose.yaml -f ./docker-compose.override.yaml'

    /bin/bash -c "docker compose exec ${project_files} -d amqp rabbitmqctl purge_queue publications -p ${rabbitmq_vhost}"
    /bin/bash -c "docker compose exec ${project_files} -d amqp rabbitmqctl purge_queue failures -p ${rabbitmq_vhost}"
}

function remove_exited_containers() {
    /bin/bash -c "docker ps -a | grep Exited | awk ""'"'{print $1}'"'"" | xargs docker rm -f >> /dev/null 2>&1"
}

function run_command {
    local php_command
    php_command=${1}

    local rabbitmq_output_log
    rabbitmq_output_log="var/log/rabbitmq.out.log"

    local rabbitmq_error_log
    rabbitmq_error_log="var/log/rabbitmq.error.log"

    local PROJECT_DIR
    PROJECT_DIR='.'

    ensure_log_files_exist "${rabbitmq_output_log}" "${rabbitmq_error_log}"

    rabbitmq_output_log="${PROJECT_DIR}/${rabbitmq_output_log}"
    rabbitmq_error_log="${PROJECT_DIR}/${rabbitmq_error_log}"

    export SCRIPT="${php_command}"

    if [ -n "${memory_limit}" ];
    then
        export PHP_MEMORY_LIMIT=' -d memory_limit='"${memory_limit}"
    fi

    echo 'Logging standard output of worker in '"${rabbitmq_output_log}"
    echo 'Logging standard error of worker in '"${rabbitmq_error_log}"

    execute_command "${rabbitmq_output_log}" "${rabbitmq_error_log}"
}

function run_php() {
    local arguments
    arguments="$(cat -)"

    if [ -z "${arguments}" ];
    then
        arguments="${ARGUMENT}"
    fi

    cd ./provisioning/containers || exit

    local override_option
    override_option=' -f ./docker-compose.yaml'

    if [ -e './docker-compose.override.yaml' ];
    then
        override_option=' -f ./docker-compose.yaml -f ./docker-compose.override.yaml'
    fi

    local command
    command=$(echo -n 'docker compose'"${override_option}"' exec -T worker '"${arguments}")

    echo 'About to execute '"${command}"
    /bin/bash -c "${command}"
}

function run_php_script() {
    local script
    script="${1}"

    local interactive_mode
    interactive_mode="${2}"

    if [ -z "${interactive_mode}" ];
    then
      interactive_mode="${INTERACTIVE_MODE}";
    fi

    if [ -z "${script}" ];
    then
      if [ -z "${SCRIPT}" ];
      then
        echo 'Please pass a valid path to a script by export an environment variable'
        echo 'e.g.'
        echo 'export SCRIPT="bin/console cache:clear"'
        return
      fi

      script="${SCRIPT}"
    fi

    local namespace=
    namespace=''

    if [ -n "${NAMESPACE}" ];
    then

        namespace="${NAMESPACE}-"

        printf '%s.%s' 'About to run container in namespace "'"${NAMESPACE}"'"' $'\n';

    fi

    local suffix
    suffix='-'"${namespace}""$(cat /dev/urandom | tr -cd 'a-f0-9' | head -c 32 2>> /dev/null)"

    export SUFFIX="${suffix}"

    local symfony_environment
    symfony_environment="$(get_symfony_environment)"

    local option_detached
    option_detached=''
    if [ -z "${interactive_mode}" ];
    then
        option_detached='-d '
    fi

    local project_name
    project_name="$(get_project_name)"

    if [ $? -gt 0 ];
    then
        printf 'A %s is expected as %s ("%s").%s' 'A non-empty string' 'project name' 'PROJECT_NAME environment variable' $'\n' 1>&2
        printf '%s%s' 'example:' $'\n' 1>&2
        printf '%s%s' 'export PROJECT_NAME="worker.example.org"' '\n' 1>&2

        return 1
    fi

    local container_name
    container_name="$(echo "${project_name}-${script}" | sha256sum | awk '{print $1}')"

    local override_option
    override_option=' -f ./docker-compose.yaml'
    if [ -e './provisioning/containers/docker-compose.override.yaml' ];
    then
        override_option=' -f ./docker-compose.yaml -f ./docker-compose.override.yaml'
    fi

    local command
    if [ -z "${interactive_mode}" ];
    then

        command="$(echo -n 'cd provisioning/containers && \
        docker compose '"${override_option}"' \
        run -e '"${symfony_environment}"' -T --rm \
        --name='"${container_name}"' '"${option_detached}"'worker '"${script}")"

    else

        command="$(echo -n 'cd provisioning/containers && \
        docker compose '"${override_option}"' \
        exec -e '"${symfony_environment}"' '"${option_detached}"'worker '"${script}")"

    fi

    echo 'About to execute "'"${command}"'"'
    /bin/bash -c "${command}"
}

function run_php_unit_tests() {
    if [ -z "${DEBUG}" ];
    then
        bin/phpunit -c ./phpunit.xml.dist --process-isolation
        return
    fi

    bin/phpunit -c ./phpunit.xml.dist --verbose --debug
}

function run_worker() {
    cd provisioning/containers || exit

    local project_files
    project_files='-f ./docker-compose.yaml -f ./docker-compose.override.yaml'

    /bin/bash -c "docker compose ${project_files} up worker"

    cd ../..
}

function set_up_amqp_queues() {
  local script
  script='php bin/console messenger:setup-transports -vvvv'

  run_php_script "${script}" 'interactive_mode'
}

function stop_workers() {
    cd provisioning/containers || exit

    local project_files
    project_files='-f ./docker-compose.yaml -f ./docker-compose.override.yaml'

    /bin/bash -c "docker compose ${project_files} exec worker bin/console messenger:stop-workers"
}
