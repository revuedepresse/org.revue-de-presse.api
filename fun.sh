#!/usr/bin/env bash
set -Eeuo pipefail

export COMPOSE_PROJECT_NAME='revue-de-presse-org'

function _set_up_configuration_files() {
    if [ ! -e ./provisioning/containers/docker-compose.override.yaml ]; then
        cp ./provisioning/containers/docker-compose.override.yaml{.dist,}
    fi

    if [ ! -e ./.env.local ]; then
        cp --verbose ./.env.local{.dist,}
    fi

    if [ ! -e ./.env ]; then
        touch ./.env
    fi

    source ./.env.local
}

function _set_file_permissions() {
    local temporary_directory
    temporary_directory="${1}"

    if [ -z "${temporary_directory}" ];
    then
        printf 'A %s is expected as %s (%s).%s' 'non-empty string' '1st argument' 'temporary directory file path' $'\n'

        return 1;
    fi

    if [ ! -d "${temporary_directory}" ];
    then
        printf 'A %s is expected as %s (%s).%s' 'directory' '1st argument' 'temporary directory file path' $'\n'

        return 1;
    fi

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        run \
        --rm \
        --user root \
        --volume "${temporary_directory}:/tmp/remove-me" \
        app \
        /bin/bash -c 'chmod -R ug+w /tmp/remove-me'
}

function build() {
    docker compose \
        --file=./provisioning/containers/docker-compose.yaml \
        --file=./provisioning/containers/docker-compose.override.yaml \
        build \
        --build-arg "WORKER_UID=${WORKER_UID}" \
        --build-arg "WORKER_GID=${WORKER_GID}" \
        app \
        cache \
        service \
        worker
}

function clean() {
    local temporary_directory
    temporary_directory="${1}"

    if [ -n "${temporary_directory}" ];
    then
        printf 'About to remove "%s".%s' "${temporary_directory}" $'\n'

        _set_file_permissions "${temporary_directory}"

        return 0
    fi

    local DEBUG

    source ./.env.local

    docker ps -a |
        \grep "${COMPOSE_PROJECT_NAME}" |
        \grep 'app' |
        awk '{print $1}' |
        xargs -I{} docker rm -f {}

    if [ -n "${DEBUG}" ];
    then
        docker images -a |
            \grep "${COMPOSE_PROJECT_NAME}" |
            \grep 'app' |
            awk '{print $3}' |
            xargs -I{} docker rmi {}

        build
    fi
}

function kill_existing_consumers {
    local pids
    pids=( $(ps ux | grep "rabbitmq:consumer" | grep -v '/bash' | grep -v grep | cut -d ' ' -f 2-3) )

    local totalProcesses
    totalProcesses=$(ps ux | grep "rabbitmq:consumer" | grep -v grep | grep -c '')

    if [ -n "${DOCKER_MODE}" ];
    then
        remove_exited_containers
    fi

    if [ "${totalProcesses}" == "0" ] ||  [ -z "${totalProcesses}" ];
    then
        echo 'No consumption processes left to kill'
        return;
    fi

    echo 'The total consumption processes counted is '"${totalProcesses}"

    if [ -z "${MAX_PROCESSES}" ];
    then
        MAX_PROCESSES=2
    fi

    echo 'The maximum processes to be kept alive is '"${MAX_PROCESSES}"

    if [ -n "${DOCKER_MODE}" ];
    then
        totalProcesses="$(docker ps -a | grep php | grep -c '')"
    fi

    if [ $(expr 0 + "${totalProcesses}") -le $(expr 0 + "${MAX_PROCESSES}") ];
    then
        return
    fi

    if [ -z "${pids}" ];
    then
        return
    fi

    if [ -n "${DOCKER_MODE}" ];
    then
        make remove-php-container

        return
    fi

    export IFS=$'\n'

    for pid in ${pids[@]};
    do echo 'About to kill process with pid '"${pid}" && \
        _pid=$(echo 0 + `echo "${pid}" | sed -e "s/[[:space:]]+//g"` | bc) && \
        kill -9 ${_pid} && \
        echo 'Just killed process of pid "'${_pid}'" consuming messages'
    done
}

function stop_workers() {
    local script
    script='bin/console messenger:stop-workers -e prod'
    command="docker compose exec -T worker ${script}"

    echo '=> About to stop consumers'
    /bin/bash -c "${command}"
}

function handle_messages {
    local command_suffix
    command_suffix="${1}"

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
        export PROJECT_DIR='/var/www/revue-de-presse.org'
    fi

    local minimum_execution_time=10
    if [ -n "${MINIMUM_EXECUTION_TIME}" ];
    then
        minimum_execution_time="${MINIMUM_EXECUTION_TIME}"
    fi

    remove_exited_containers

    local rabbitmq_output_log="./var/log/rabbitmq.${NAMESPACE}.out.log"
    local rabbitmq_error_log="./var/log/rabbitmq.${NAMESPACE}.error.log"
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

    export SCRIPT="${php_directives}bin/console messenger:consume --time-limit=${TIME_LIMIT} -m ${MEMORY_LIMIT} -l ${MESSAGES} "${command_suffix}

    cd "${PROJECT_DIR}/provisioning/containers" || exit

    command="docker compose run --rm --name ${SUPERVISOR_PROCESS_NAME} -T worker ${SCRIPT}"
    echo 'Executing command: "'$command'"'
    echo 'Logging standard output of RabbitMQ messages consumption in '"${rabbitmq_output_log}"
    echo 'Logging standard error of RabbitMQ messages consumption in '"${rabbitmq_error_log}"
    /bin/bash -c "$command >> ${rabbitmq_output_log} 2>> ${rabbitmq_error_log}"
    cd "../../"

    /bin/bash -c "sleep ${minimum_execution_time}"
}

function consume_amqp_lively_status_messages {
    handle_messages 'timely_status' 'consumer'
}

function consume_amqp_messages_for_aggregates_likes {
    handle_messages 'aggregates_likes' 'consumer'
}

function consume_amqp_messages_for_networks {
    handle_messages 'network' 'consumer'
}

function consume_amqp_messages_for_aggregates_status {
    handle_messages 'aggregates_status'
}

function consume_amqp_messages_for_member_status {
    handle_messages 'user_status'
}

function consume_amqp_messages_for_news_status {
    handle_messages 'news_status'
}

function purge_queues() {
    local rabbitmq_vhost
    rabbitmq_vhost="$(cat <(cat .env.local | grep STATUS=amqp | sed -E 's#.+(/.+)/[^/]*$#\1#' | sed -E 's/\/%2f/\//g'))"

    cd provisioning/containers || exit

    /bin/bash -c "docker compose exec -d messenger rabbitmqctl purge_queue get-news-status -p ${rabbitmq_vhost}"
    /bin/bash -c "docker compose exec -d messenger rabbitmqctl purge_queue get-news-likes -p ${rabbitmq_vhost}"
    /bin/bash -c "docker compose exec -d messenger rabbitmqctl purge_queue failures -p ${rabbitmq_vhost}"
}

function stop_workers() {
    cd provisioning/containers || exit

    docker compose run --rm worker bin/console messenger:stop-workers
}

function execute_command () {
    local output_log="${1}"
    local error_log="${2}"

    cd "${PROJECT_DIR}" || exit

    make run-php-script >> "${output_log}" 2>> "${error_log}"

    if [ -n "${VERBOSE}" ];
    then
        cat "${output_log}" | tail -n1000
        cat "${error_log}" | tail -n1000
    fi
}

function get_mysql_gateway() {
    local gateway=`ip -f inet addr  | grep docker0 -A1 | cut -d '/' -f 1 | grep inet | sed -e 's/inet//' -e 's/\s*//g'`
    echo "${gateway}"
}

function grant_privileges {
    local database_user_test
    database_user_test="$(get_param_value_from_config "database_user_test")"

    local database_name_test
    database_name_test="$(get_param_value_from_config "database_name_test")"

    local database_password_test
    database_password_test="$(get_param_value_from_config "database_password_test")"

    local gateway
    gateway="$(get_mysql_gateway)"

    cat provisioning/containers/mysql/templates/grant-privileges-to-testing-user.sql.dist | \
        sed -e 's/{database_name_test}/'"${database_name_test}"'/g' \
        -e 's/{database_user_test}/'"${database_user_test}"'/g' \
        -e 's/{database_password_test}/'"${database_password_test}"'/g' \
        -e 's/{gateway}/'"${gateway}"'/g' \
        >  provisioning/containers/mysql/templates/grant-privileges-to-testing-user.sql

    docker exec -ti mysql mysql -uroot \
        -e "$(cat provisioning/containers/mysql/templates/grant-privileges-to-testing-user.sql)"

    local database_user
    database_user="$(get_param_value_from_config "database_user")"

    local database_name
    database_name="$(get_param_value_from_config "database_name")"

    local database_password
    database_password="$(get_param_value_from_config "database_password")"

    cat ./provisioning/containers/mysql/templates/grant-privileges-to-user.sql.dist | \
        sed -e 's/{database_name}/'"${database_name}"'/g' \
        -e 's/{database_user}/'"${database_user}"'/g' \
        -e 's/{database_password}/'"${database_password}"'/g' \
        -e 's/{gateway}/'"${gateway}"'/g' \
        >  provisioning/containers/mysql/templates/grant-privileges-to-user.sql

    docker exec -ti mysql mysql -uroot \
        -e "$(cat provisioning/containers/mysql/templates/grant-privileges-to-user.sql)"
}

function create_database_schema {
    local env="${1}"

    if [ -z "${env}" ];
    then
        echo 'Please pass a valid environment ("test", "dev" or "prod")'
    fi

    local project_dir
    project_dir="$(get_project_dir)"

    echo 'php /var/www/revue-de-presse.org/bin/console doctrine:schema:create -e '"${env}" | make run-php
}

function create_database_test_schema {
    create_database_schema "test"
}

function create_database_prod_like_schema {
    create_database_schema "prod"
}

function get_param_value_from_config() {
    local name="${1}"

    if [ -z "${name}" ];
    then
        echo 'Please provide the non-empty name of a parameter available in the configuration, which has not been commented out.'
    fi

    local param_value
    param_value=`cat app/config/parameters.yml | grep "${name}"':' | grep -v '#' | \
        cut -f 2 -d ':' | sed -e 's/[[:space:]]//g' -e 's/^"//' -e 's/"$//'`

    echo "${param_value}"
}

function compute_schema_differences_for_read_database() {
    run_php_script "php /var/www/revue-de-presse.org/bin/console doc:mig:diff -vvvv --em=default -n" interactive_mode
}

function compute_schema_differences_for_write_database() {
    run_php_script "php /var/www/revue-de-presse.org/bin/console doc:mig:diff -vvvv --em=write -n" interactive_mode
}

function migrate_schema_of_read_database() {
    run_php_script "php /var/www/revue-de-presse.org/bin/console doc:mig:mig --em=default" interactive_mode
}

function migrate_schema_of_write_database() {
    run_php_script "php /var/www/revue-de-presse.org/bin/console doc:mig:mig --em=write" interactive_mode
}

function cache_clear_warmup() {
    local WORKER_UID
    local WORKER_GID

    _set_up_configuration_files

    local reuse_existing_container
    reuse_existing_container="${1}"

    if [ -z "${reuse_existing_container}" ];
    then
        remove_container_image 'app'

        docker compose \
            -f ./provisioning/containers/docker-compose.yaml \
            -f ./provisioning/containers/docker-compose.override.yaml \
            up \
            --detach \
            app
    fi

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        exec \
        --user "${WORKER_UID}:${WORKER_GID}" \
        app \
        /bin/bash -c '. /scripts/clear-cache.sh'

    clean ''
}

function install {
    _set_up_configuration_files

    clean ''

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        up -d app

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        exec \
        --user root \
        -T app \
        /bin/bash -c 'source /scripts/install-app-requirements.sh'

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        down

    cache_clear_warmup --reuse-existing-container
}

function run_composer {
    local command=''
    if [ -z "${COMMAND}" ];
    then
        command="${COMMAND}"
        echo 'Please pass a non-empty command as environment variable'
        echo 'e.g.'
        echo 'export COMMAND="install --prefer-dist"'
        return
    fi

    command="${COMMAND}"

    local command
    command=$(echo -n 'php /bin/bash -c "php -dmemory_limit="-1" '"${project_dir}"'/composer.phar "'"${command}")
    echo ${command} | make run-php
}

function run_composer {
    local command
    command=''

    if [ -z "${COMMAND}" ];
    then
        command="${COMMAND}"
        echo 'Please pass a non-empty command as environment variable'
        echo 'e.g.'
        echo 'export COMMAND="install --prefer-dist"'
        return
    fi

    command="${COMMAND}"

    local command
    command=$(echo -n 'php /bin/bash -c "php -dmemory_limit="-1" '"${project_dir}"'/composer.phar "'"${command}")
    echo ${command} | make run-php
}

function run_mysql_client {
    docker exec -ti mysql mysql -uroot -A
}

function remove_mysql_container {
    if [ `docker ps -a | grep mysql | grep -c ''` -gt 0 ];
    then
        docker rm -f `docker ps -a | grep mysql | awk '{print $1}'`
    fi
}

function run_mysql_container {
    local from="${1}"

    if [ -n "${from}" ];
    then
        echo 'About to move to "'"${from}"'"'
        cd "${from}" || exit
    fi

    local database_password
    database_password="$(get_param_value_from_config "database_password_admin")"

    local database_name
    database_name="$(get_param_value_from_config "database_name_admin")"

    local database_user
    database_user="$(get_param_value_from_config "database_user_admin")"

    echo 'Database name is "'"${database_name}"'"'
    echo 'User name is '"${database_user}*****"

    local obfuscated_password
    obfuscated_password=$(/bin/bash -c 'echo "'"${database_password}"'" | head -c5')

    echo 'User password would be like '"${obfuscated_password}*****"

    cd ./provisioning/containers/mysql || exit

    local replacement_pattern='s/{password\}/'"${database_password}"'/'
    cat ./templates/my.cnf.dist | sed -e "${replacement_pattern}" > ./templates/my.cnf

    remove_mysql_container

    local initializing
    initializing=1

    local configuration_volume
    configuration_volume='-v '"`pwd`"'/templates/my.cnf:/etc/mysql/conf.d/config-file.cnf '

    if [ -z "${INIT}" ];
    then
        # Credentials yet to be granted can not be configured at initialization
        configuration_volume=''
        initializing=0
    fi

    local gateway
    gateway="`get_mysql_gateway`"

    local mysql_volume_path
    mysql_volume_path=`pwd`"/../../volumes/mysql"

    if [ -n "${MYSQL_VOLUME}" ];
    then
        mysql_volume_path="${MYSQL_VOLUME}"
        configuration_volume='-v '"`pwd`"'/templates/my.cnf:/etc/mysql/conf.d/config-file.cnf '
        echo 'About to mount "'"${MYSQL_VOLUME}"'" as MySQL volume'
    fi

    # @see https://hub.docker.com/_/mysql/
    command="docker run --restart=always -d -p${gateway}:3306:3306 --name mysql \
        -e MYSQL_DATABASE=${database_name} \
        -e MYSQL_USER=${database_user} \
        -e MYSQL_PASSWORD=${database_password} \
        -e MYSQL_ROOT_PASSWORD=${database_password} \
        ${configuration_volume} -v ${mysql_volume_path}:/var/lib/mysql \
        mysql:5.7 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci"

    # Restore current directory to project root dir
    cd ./../../../

    /bin/bash -c "echo 'About to execute command: ${command}'"
    /bin/bash -c "${command}"

    if [ "${initializing}" -eq 0 ];
    then
        local last_container_id
        last_container_id="$(docker ps -ql)"

        local last_container_logs
        last_container_logs="$(docker logs "${last_container_id}" 2>&1)"

        while [ $(echo "${last_container_logs}" | grep -c "\.sock") -eq 0 ];
        do
            sleep 1
            last_container_logs="$(docker logs "${last_container_id}" 2>&1)"

            test $(echo "${last_container_logs}" | grep -c "\.sock") -eq 0 && echo -n '.'
        done

        local matching_databases
        matching_databases=$(docker exec -ti "${last_container_id}" mysql \-e 'show databases' | \
            grep weaving_dev | grep -c '')

        if [ ${matching_databases} -eq 0 ];
        then
            grant_privileges && \
            create_database_prod_like_schema
        fi
    fi

    # Log the last created container on initialization
    if [ ${initializing} -eq 1 ];
    then

        local last_container_id
        last_container_id="$(docker ps -ql)"

        local last_container_logs
        last_container_logs="$(docker logs "${last_container_id}" 2>&1)"

        while [ $(echo "${last_container_logs}" | grep -c "\.sock") -eq 0 ];
        do
            sleep 1
            last_container_logs="$(docker logs "${last_container_id}" 2>&1)"

            test $(echo "${last_container_logs}" | grep -c "\.sock") -eq 0 && echo -n '.' \
            || printf "\n"%s ''
        done

        remove_mysql_container

        unset INIT
        run_mysql_container $(pwd)
    else
        local last_container_id
        last_container_id="$(docker ps -a | grep mysql | awk '{print $1}')"

        docker exec -ti "${last_container_id}" mysql
    fi
}

function initialize_mysql_volume {
    remove_mysql_container
    sudo rm -rf ./provisioning/volumes/mysql/*

    export INIT=1
    run_mysql_container # Will clean up INIT global var
}

function remove_rabbitmq_container {
    if [ $(docker ps -a | grep rabbitmq -c) -eq 0 ]
    then
        return;
    fi

    docker ps -a | grep rabbitmq | awk '{print $1}' | xargs docker rm -f
}

function run_rabbitmq_container {
    local rabbitmq_vhost
    rabbitmq_vhost="$(cat <(cat ../backup/app/config/parameters.yml | grep -v '#' | grep 'rabbitmq_vhost:' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'))"

    local rabbitmq_password
    rabbitmq_password="$(cat ../backup/app/config/parameters.yml | grep -v '#' | grep 'rabbitmq_password:' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g')"

    local rabbitmq_user
    rabbitmq_user=$(cat <(cat ../backup/app/config/parameters.yml | \
        grep 'rabbitmq_user:' | grep -v '#' | \
        cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'))

    echo 'RabbitMQ user is "'"${rabbitmq_user}"'"'
    echo 'RabbitMQ password is "'"${rabbitmq_password}"'"'
    echo 'RabbitMQ vhost is "'"${rabbitmq_vhost}"'"'

    cd ./provisioning/containers/rabbitmq || exit

    remove_rabbitmq_container

    local gateway
    gateway=`ifconfig | grep docker0 -A1 | tail -n1 | awk '{print $2}' | sed -e 's/addr://'`

    local cmd
    cmd="docker run -d -p${gateway}:5672:5672 \
    --name rabbitmq \
    --restart=always \
    -e RABBITMQ_DEFAULT_USER=${rabbitmq_user} \
    -e RABBITMQ_DEFAULT_PASS='""$(cat <(/bin/bash -c "${rabbitmq_password}"))""' \
    -e RABBITMQ_DEFAULT_VHOST=${rabbitmq_vhost} \
    -v `pwd`/../../volumes/rabbitmq:/var/lib/rabbitmq \
    rabbitmq:3.7-management"

    echo "${cmd}"

    /bin/bash -c "${cmd}"
}

function remove_exited_containers() {
    /bin/bash -c "docker ps -a | grep Exited | awk ""'"'{print $1}'"'"" | xargs docker rm -f >> /dev/null 2>&1"
}

function list_amqp_queues() {
    local rabbitmq_vhost
    rabbitmq_vhost="$(cat <(cat .env.local | grep STATUS=amqp | sed -E 's#.+(/.+)/[^/]*$#\1#' | sed -E 's/\/%2f/\//g'))"

    cd provisioning/containers || exit

    /bin/bash -c "docker compose exec messenger watch -n1 'rabbitmqctl list_queues -p ${rabbitmq_vhost}'"
}

function start() {
    clean ''

    local command
    command=$(cat <<-SCRIPT
docker compose \
      --file=./provisioning/containers/docker-compose.yaml \
      --file=./provisioning/containers/docker-compose.override.yaml \
			up \
			--detach \
			service
SCRIPT
)

    echo 'About to execute "'"${command}"'"'
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

    local memory

    memory=''

    if [ -n "${PHP_MEMORY_LIMIT}" ];
    then
        memory="${PHP_MEMORY_LIMIT}"
    fi

    local namespace

    namespace=''
    if [ -n "${NAMESPACE}" ];
    then
        namespace="${NAMESPACE}-"

        echo 'About to run container in namespace '"${NAMESPACE}"
    fi

    local suffix
    suffix='-'"${namespace}""$(cat /dev/urandom | tr -cd 'a-f0-9' | head -c 32 2>> /dev/null)"

    export SUFFIX="${suffix}"

    local option_detached
    option_detached=''

    if [ -z "${interactive_mode}" ];
    then
        option_detached='-d '
    fi

    local container_name
    container_name="$(echo "${script}" | sha256sum | awk '{print $1}')"

    local command

    if [ -z "${interactive_mode}" ];
    then
        command="$(echo -n 'cd provisioning/containers && \
            docker compose \
                --file=docker-compose.yaml \
                --file=docker-compose.override.yaml \
                run \
                -T \
                --rm \
                --name='"${container_name}"' '"${option_detached}"'worker '"${script}")"
    else
        command="$(echo -n 'cd provisioning/containers && \
           docker compose \
                --file=docker-compose.yaml \
                --file=docker-compose.override.yaml \
                exec '"${option_detached}"'worker '"${script}")"
    fi

    echo 'About to execute "'"${command}"'"'
    /bin/bash -c "${command}"
}

function run_stack() {
    cd provisioning/containers || exit
    docker compose up
    cd ../..
}

function run_worker() {
    cd provisioning/containers || exit
    docker compose up -d worker
    cd ../..
}

function ensure_log_files_exist() {
    local standard_output_file="${1}"
    local standard_error_file="${2}"

    cd "${PROJECT_DIR}"

    if [ ! -e "${standard_output_file}" ];
    then
        sudo touch "${standard_output_file}";
    fi

    if [ ! -e "${standard_error_file}" ];
    then
        sudo touch "${standard_error_file}";
    fi

    if [ ! `whoami` == 'www-data' ];
    then
        sudo chown www-data "${standard_output_file}" "${standard_error_file}"
        sudo chmod a+rwx "${standard_output_file}" "${standard_error_file}"
    fi
}

function get_symfony_environment() {
    local symfony_env='dev'
    if [ -n "${SYMFONY_ENV}" ];
    then
        symfony_env="${SYMFONY_ENV}"
    fi

    echo 'APP_ENV='"${symfony_env}"
}

function get_environment_option() {
    local symfony_env='dev'
    if [ -n "${SYMFONY_ENV}" ];
    then
        symfony_env="${SYMFONY_ENV}"
    fi

    echo ' APP_ENV='"${symfony_env}"
}

function before_running_command() {
    make remove-php-container

    export XDEBUG_CONFIG="idekey='phpstorm-xdebug'"

    if [ -z "${PROJECT_DIR}" ];
    then
        export PROJECT_DIR='/var/www/revue-de-presse.org'
    fi
}

function run_command {
    local php_command=${1}
    local memory_limit=${2}

    local rabbitmq_output_log="var/log/rabbitmq.${NAMESPACE}.out.log"
    local rabbitmq_error_log="var/log/rabbitmq.${NAMESPACE}.error.log"

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

    echo 'Logging standard output of RabbitMQ messages consumption in '"${rabbitmq_output_log}"
    echo 'Logging standard error of RabbitMQ messages consumption in '"${rabbitmq_error_log}"

    execute_command "${rabbitmq_output_log}" "${rabbitmq_error_log}"
}

function dispatch_messages_for_news_list {
    if [ -z ${NAMESPACE} ];
    then
        export NAMESPACE="produce_news_messages"
    fi

    before_running_command

    if [ -z "${username}" ];
    then
        echo 'Please export a valid username: export username="bob"'

        return
    fi

    local priority_option=''
    if [ -n "${in_priority}" ];
    then
        priority_option='--priority_to_aggregates '
    fi

    local query_restriction=''
    if [ -n "${QUERY_RESTRICTION}" ];
    then
        query_restriction='--query_restriction='"${QUERY_RESTRICTION}"
    fi

    local list_option
    list_option=''
    if [ -n "${list_name}" ];
    then
        local list_option='--list='"'${list_name}'"
        if [ -n "${multiple_lists}" ];
        then
            list_option='--lists='"'${multiple_lists}'"
        fi
    fi

    local cursor_argument
    cursor_argument=''
    if [ -n "${CURSOR}" ];
    then
        cursor_argument=' --cursor='"${CURSOR}"
    fi

    local arguments
    arguments="${priority_option}"'--screen_name='"${username}"' '"${list_option}"' '"${query_restriction}"
    arguments="${arguments}${cursor_argument}"
    run_command 'bin/console press-review:dispatch-messages-to-fetch-member-statuses '"${arguments}"
}

function refresh_statuses() {
    export NAMESPACE="refresh_statuses"
    make remove-php-container

    export XDEBUG_CONFIG="idekey='phpstorm-xdebug'"

    if [ -z "${PROJECT_DIR}" ];
    then
        export PROJECT_DIR='/var/www/revue-de-presse.org'
    fi

    local rabbitmq_output_log="var/log/rabbitmq.${NAMESPACE}.out.log"
    local rabbitmq_error_log="var/log/rabbitmq.${NAMESPACE}.error.log"
    ensure_log_files_exist "${rabbitmq_output_log}" "${rabbitmq_error_log}"
    rabbitmq_output_log="${PROJECT_DIR}/${rabbitmq_output_log}"
    rabbitmq_error_log="${PROJECT_DIR}/${rabbitmq_error_log}"

    if [ -z "${aggregate_name}" ];
    then
        echo 'Please export a valid aggregate name: export aggregate_name="news"'

        return
    fi

    local php_command
    php_command='bin/console press-review:map-aggregate-status-collection --aggregate-name="'"${aggregate_name}"'" -vvv'

    if [ -z "${DOCKER_MODE}" ];
    then
        command="/usr/bin/php $PROJECT_DIR/${php_command}"
        echo 'Executing command: "'$command'"'
        echo 'Logging standard output of RabbitMQ messages consumption in '"${rabbitmq_output_log}"
        echo 'Logging standard error of RabbitMQ messages consumption in '"${rabbitmq_error_log}"
        /bin/bash -c "$command >> ${rabbitmq_output_log} 2>> ${rabbitmq_error_log}"

        return
    fi

    export SCRIPT="${php_command}"

    echo 'Logging standard output of RabbitMQ messages consumption in '"${rabbitmq_output_log}"
    echo 'Logging standard error of RabbitMQ messages consumption in '"${rabbitmq_error_log}"

    execute_command "${rabbitmq_output_log}" "${rabbitmq_error_log}"
}

function run_php_unit_tests() {
    if [ -z ${DEBUG} ];
    then
        bin/phpunit -c ./phpunit.xml.dist --process-isolation
        return
    fi

    bin/phpunit -c ./phpunit.xml.dist --verbose --debug
}

function run_php_features_tests() {
    bin/behat -c ./behat.yml
}

function remove_redis_container {
    if [ $(docker ps -a | grep redis | grep -c '') -gt 0 ];
    then
        docker rm -f $(docker ps -a | grep redis | awk '{print $1}')
    fi
}

function today_statuses() {
    cat ./var/log/dev.log \
    | awk '{$1=$2=$3="";print $0}' \
    | sed -e 's/^\s\+//' \
    | grep "$(date -I)" \
    | awk '{$1=$2="";print $0}'
}

function follow_today_statuses() {
    tail -f ./var/log/dev.log \
    | awk '{$1=$2=$3="";print $0}' \
    | sed -e 's/^\s\+//' \
    | grep "$(date -I)" \
    | awk '{$1=$2="";print $0}'
}

set +Eeuo pipefail
