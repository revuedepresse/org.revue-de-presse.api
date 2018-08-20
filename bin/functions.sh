#!/usr/bin/env bash

function create_network() {
    local network=`get_docker_network`
    /bin/bash -c 'docker network create '"${network}"
}

function get_network_option() {
    network='--network "$(get_docker_network)" '
    if [ ! -z "${NO_DOCKER_NETWORK}" ];
    then
        network=''
    fi

    echo "${network}";
}

function kill_existing_consumers {
    local pids=(`ps ux | grep "rabbitmq:consumer" | grep -v '/bash' | grep -v grep | cut -d ' ' -f 2-3`)
    local totalProcesses=`ps ux | grep "rabbitmq:consumer" | grep -v grep | grep -c ''`


    if [ ! -z "${DOCKER_MODE}" ];
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

    if [ ! -z ${DOCKER_MODE} ];
    then
        totalProcesses="$(docker ps -a | grep php | grep -c '')"
    fi

    if [ `expr 0 + "${totalProcesses}"` -le `expr 0 + "${MAX_PROCESSES}"` ];
    then
        return
    fi

    if [ -z "${pids}" ];
    then
        return
    fi

    if [ ! -z "${DOCKER_MODE}" ];
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

function consume_amqp_messages {
    local command_suffix="${1}"

    export NAMESPACE="consume_amqp_messages"

    export XDEBUG_CONFIG="idekey='phpstorm-xdebug'"

    if [ -z "${MESSAGES}" ]
    then
        MESSAGES=10;
        echo '[default count of messages] '$MESSAGES
    fi

    if [ -z "${MEMORY_LIMIT}" ]
    then
        MEMORY_LIMIT=64;
        echo '[default memory limit] '$MEMORY_LIMIT
    fi

    if [ -z "${PROJECT_DIR}" ];
    then
        export PROJECT_DIR='/var/www/devobs'
    fi

    remove_exited_containers

    local rabbitmq_output_log="app/logs/rabbitmq."${NAMESPACE}".out.log"
    local rabbitmq_error_log="app/logs/rabbitmq."${NAMESPACE}".error.log"
    ensure_log_files_exist "${rabbitmq_output_log}" "${rabbitmq_error_log}"
    rabbitmq_output_log="${PROJECT_DIR}/${rabbitmq_output_log}"
    rabbitmq_error_log="${PROJECT_DIR}/${rabbitmq_error_log}"

    env_option="$(get_environment_option)"
    export SCRIPT="app/console rabbitmq:consumer -l $MEMORY_LIMIT -w -m $MESSAGES weaving_the_web_amqp.twitter.""${command_suffix}""$env_option -vvv"

    local symfony_environment="$(get_symfony_environment)"

    if [ -z "${DOCKER_MODE}" ];
    then
        command="${symfony_environment} /usr/bin/php $PROJECT_DIR/""${SCRIPT}"
        echo 'Executing command: "'$command'"'
        echo 'Logging standard output of RabbitMQ messages consumption in '"${rabbitmq_output_log}"
        echo 'Logging standard error of RabbitMQ messages consumption in '"${rabbitmq_error_log}"
        /bin/bash -c "$command >> ${rabbitmq_output_log} 2>> ${rabbitmq_error_log}"

        return
    fi

    echo 'Logging standard output of RabbitMQ messages consumption in '"${rabbitmq_output_log}"
    echo 'Logging standard error of RabbitMQ messages consumption in '"${rabbitmq_error_log}"

    execute_command "${rabbitmq_output_log}" "${rabbitmq_error_log}"
}

function consume_amqp_messages_for_member_status {
    consume_amqp_messages 'user_status'
}

function consume_amqp_messages_for_news_status {
    consume_amqp_messages 'news_status'
}

function execute_command () {
    local output_log="${1}"
    local error_log="${2}"

    cd "${PROJECT_DIR}"
    make run-php-script >> "${output_log}" 2>> "${error_log}"

    if [ ! -z "${VERBOSE}" ];
    then
        cat "${output_log}" | tail -n1000
        cat "${error_log}" | tail -n1000
    fi
}

function grant_privileges {
    local database_user_test=`cat app/config/parameters.yml | grep 'database_user_test:' | grep -v '#' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'`
    local database_name_test=`cat app/config/parameters.yml | grep 'database_name_test:' | grep -v '#' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'`
    cat provisioning/containers/mysql/templates/grant-privileges-to-testing-user.sql.dist | \
        sed -e 's/{database_name_test}/'"${database_name_test}"'/g' \
        -e 's/{database_user_test}/'"${database_user_test}"'/g' \
        >  provisioning/containers/mysql/templates/grant-privileges-to-testing-user.sql

    docker exec -ti mysql mysql -uroot \
        -e "$(cat provisioning/containers/mysql/templates/grant-privileges-to-testing-user.sql)"

    local database_user=`cat app/config/parameters.yml | grep 'database_user:' | grep -v '#' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'`
    local database_name=`cat app/config/parameters.yml | grep 'database_name:' | grep -v '#' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'`
    cat provisioning/containers/mysql/templates/grant-privileges-to-user.sql.dist | \
        sed -e 's/{database_name}/'"${database_name}"'/g' \
        -e 's/{database_user}/'"${database_user}"'/g' \
        >  provisioning/containers/mysql/templates/grant-privileges-to-user.sql

    docker exec -ti mysql mysql -uroot \
        -e "$(cat provisioning/containers/mysql/templates/grant-privileges-to-user.sql)"
}

function get_project_dir {
    local project_dir=''

    if [ ! -z "${PROJECT_DIR}" ];
    then
        project_dir="${PROJECT_DIR}"
    fi

    echo "${project_dir}"
}

function create_database_test_schema {
    local project_dir="$(get_project_dir)"
    echo 'php /var/www/devobs/app/console doctrine:schema:create -e test' | make run-php
}

function migrate_schema {
    local project_dir="$(get_project_dir)"
    echo 'php '"${project_dir}"'/app/console doc:mig:mig --em=admin' | make run-php
}

function install_php_dependencies {
    local project_dir="$(get_project_dir)"
    local command=$(echo -n 'php /bin/bash -c "cd '"${project_dir}"' &&
    source '"${project_dir}"'/bin/install-composer.sh &&
    php '"${project_dir}"'/composer.phar install --prefer-dist"')
    echo ${command} | make run-php
}

function run_mysql_client {
    docker exec -ti mysql mysql -uroot
}

function remove_mysql_container {
    if [ `docker ps -a | grep mysql | grep -c ''` -gt 0 ];
    then
        docker rm -f `docker ps -a | grep mysql | awk '{print $1}'`
    fi
}

function run_mysql_container {
    local database_password="cat ../../../app/config/parameters.yml | grep -v '#' | grep 'database_password_admin:' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'"
    local database_name=$(cat <(cat app/config/parameters.yml | \
        grep 'database_name:' | grep -v '#' \
        cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'))

    echo 'Database name is '"${database_name}"
    echo 'Database password is '"${database_password}"

    cd ./provisioning/containers/mysql

    local replacement_pattern='s/{password}/'$(cat <(/bin/bash -c "${database_password}"))'/'
    cat ./templates/my.cnf.dist | sed -e "${replacement_pattern}" > ./templates/my.cnf

    remove_mysql_container

    local configuration_volume=''
    if [ -z "${INIT}" ];
    then
        configuration_volume='-v '"`pwd`"'/templates/my.cnf:/etc/mysql/conf.d/config-file.cnf '
    fi

    local gateway=`ip -f inet addr  | grep docker0 -A1 | cut -d '/' -f 1 | grep inet | sed -e 's/inet//' -e 's/\s*//g'`

    command="docker run -d -p"${gateway}":3306:3306 --name mysql -e MYSQL_DATABASE=${database_name} \
        -e MYSQL_ROOT_PASSWORD="$(cat <(/bin/bash -c "${database_password}"))" \
        "${configuration_volume}"-v `pwd`/../../volumes/mysql:/var/lib/mysql \
        mysql:5.7"
    /bin/bash -c "${command}"
}

function initialize_mysql_volume {
    remove_mysql_container
    sudo rm -rf ./provisioning/volumes/mysql/*

    export INIT=1

    run_mysql_container

    unset INIT
}

function remove_rabbitmq_container {
    if [ `docker ps -a | grep rabbitmq -c` -eq 0 ]
    then
        return;
    fi

    docker ps -a | grep rabbitmq | awk '{print $1}' | xargs docker rm -f
}

function run_rabbitmq_container {
    local rabbitmq_vhost="$(cat <(cat app/config/parameters.yml | grep -v '#' | grep 'rabbitmq_vhost:' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'))"
    local rabbitmq_password="cat ../../../app/config/parameters.yml | grep -v '#' | grep 'rabbitmq_password:' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'"
    local rabbitmq_user=$(cat <(cat app/config/parameters.yml | \
        grep 'rabbitmq_user:' | grep -v '#' | \
        cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'))

    echo 'RabbitMQ user is "'"${rabbitmq_user}"'"'
    echo 'RabbitMQ password is "'"${rabbitmq_password}"'"'
    echo 'RabbitMQ vhost is "'"${rabbitmq_vhost}"'"'

    cd ./provisioning/containers/rabbitmq

    remove_rabbitmq_container

    local gateway=`ifconfig | grep docker0 -A1 | tail -n1 | awk '{print $2}' | sed -e 's/addr://'`

    local network=`get_network_option`
    command="docker run -d -p"${gateway}":5672:5672 \
    --name rabbitmq \
    --hostname rabbitmq ${network}\
    -e RABBITMQ_DEFAULT_USER=${rabbitmq_user} \
    -e RABBITMQ_DEFAULT_PASS='""$(cat <(/bin/bash -c "${rabbitmq_password}"))""' \
    -e RABBITMQ_DEFAULT_VHOST="${rabbitmq_vhost}" \
    -v `pwd`/../../volumes/rabbitmq:/var/lib/rabbitmq \
    rabbitmq:3.7-management"
    echo "${command}"

    /bin/bash -c "${command}"
}

function build_php_container() {
    cd provisioning/containers/php
    docker build -t php .
}

function remove_exited_containers() {
    /bin/bash -c "docker ps -a | grep Exited | awk ""'"'{print $1}'"'"" | xargs docker rm -f >> /dev/null 2>&1"
}

function remove_php_container() {
    local namespace=''
    if [ ! -z  "${NAMESPACE}" ];
    then
        namespace=' | grep '"'""${NAMESPACE}""'"
    fi

    remove_exited_containers

    local running_containers_matching_namespace="docker ps -a | grep php""${namespace}"

    local running_containers=`/bin/bash -c "${running_containers_matching_namespace} | grep -c ''"`
    if [ "${running_containers}" -eq 0 ];
    then
        echo 'No more PHP container to be removed'

        return
    fi

    command="${running_containers_matching_namespace} | awk '{print "'$1'"}' | xargs docker rm -f >> /dev/null 2>&1"
    echo '=> About to execute command "'"${command}"'"'

    /bin/bash -c "${command}" || echo 'No more container to be removed'
}

function configure_rabbitmq_user_privileges() {
    local rabbitmq_vhost="$(cat <(cat app/config/parameters.yml | grep -v '#' | grep 'rabbitmq_vhost:' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'))"
    local rabbitmq_user=$(cat <(cat app/config/parameters.yml | \
        grep 'rabbitmq_user:' | grep -v '#' | \
        cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'))
    local rabbitmq_password="cat app/config/parameters.yml | grep -v '#' | grep 'rabbitmq_password:' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'"

    docker exec -ti rabbitmq /bin/bash -c 'rabbitmqctl add_vhost '"${rabbitmq_vhost}"
    docker exec -ti rabbitmq /bin/bash -c 'rabbitmqctl add_user '"${rabbitmq_user}"' '"'""$(cat <(/bin/bash -c "${rabbitmq_password}"))""'"
    docker exec -ti rabbitmq /bin/bash -c 'rabbitmqctl set_user_tags '"${rabbitmq_user}"' administrator'
    docker exec -ti rabbitmq /bin/bash -c 'rabbitmqctl set_permissions -p '"${rabbitmq_vhost}"' '"${rabbitmq_user}"' ".*" ".*" ".*"'
}

function list_amqp_queues() {
    local rabbitmq_vhost="$(cat <(cat app/config/parameters.yml | grep -v '#' | grep 'rabbitmq_vhost:' | cut -f 2 -d ':' | sed -e 's/[[:space:]]//g'))"
    docker exec -ti rabbitmq watch -n1 'rabbitmqctl list_queues -p '"${rabbitmq_vhost}"
}

function setup_amqp_queue() {
    local=`pwd`

    local project_dir="$(get_project_dir)"
    echo 'php '"${project_dir}"'/app/console rabbitmq:setup-fabric' | make run-php
}
function list_php_extensions() {
    remove_php_container
    docker run --name php php -m
}

function get_docker_network() {
    echo 'press-review-network'
}

function run_php_script() {
    local script="${1}"

    if [ -z ${script} ];
    then
        script="${SCRIPT}"
    fi

    local namespace=''
    if [ ! -z "${NAMESPACE}" ];
    then
        namespace="${NAMESPACE}-"

        echo 'About to run container in namespace '"${NAMESPACE}"
    fi

    local suffix='-'"${namespace}""$(cat /dev/urandom | tr -cd 'a-f0-9' | head -c 32 2>> /dev/null)"

    export SUFFIX="${suffix}"
    local symfony_environment="$(get_symfony_environment)"

    local network=`get_network_option`
    local command=$(echo -n 'docker run '"${network}"'\
    -e '"${symfony_environment}"' \
    -v '`pwd`'/provisioning/containers/php/templates/20-no-xdebug.ini.dist:/usr/local/etc/php/conf.d/20-xdebug.ini \
    -v '`pwd`':/var/www/devobs \
    --name=php'"${suffix}"' php /var/www/devobs/'"${script}")

    echo 'About to execute "'"${command}"'"'

    /bin/bash -c "${command}"
}

function run_php() {
    local arguments="$(cat -)"

    if [ -z "${arguments}" ];
    then
        arguments="${ARGUMENT}"
    fi

    local suffix='-'"$(cat /dev/urandom | tr -cd 'a-f0-9' | head -c 32 2>> /dev/null)"

    export SUFFIX="${suffix}"
    local symfony_environment="$(get_symfony_environment)"

    local network=`get_network_option`
    local command=$(echo -n 'docker run '"${network}"'\
    -e '"${symfony_environment}"' \
    -v '`pwd`'/provisioning/containers/php/templates/20-no-xdebug.ini.dist:/usr/local/etc/php/conf.d/20-xdebug.ini \
    -v '`pwd`':/var/www/devobs \
    --name=php'"${suffix}"' '"${arguments}")

    echo 'About to execute '"${command}"

    /bin/bash -c "${command}"
}

function keep_php_container_running() {
    echo 'php -r "while (true) { sleep(1); } "' | make run-php
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
    if [ ! -z "${SYMFONY_ENV}" ];
    then
        symfony_env="${SYMFONY_ENV}"
    fi

    echo 'SYMFONY_ENV='"${symfony_env}"
}

function get_environment_option() {
    local symfony_env='dev'
    if [ ! -z "${SYMFONY_ENV}" ];
    then
        symfony_env="${SYMFONY_ENV}"
    fi

    echo ' --env='"${symfony_env}"
}

function produce_amqp_messages_from_members_lists {
    export NAMESPACE="produce_messages_from_members_lists"
    make remove-php-container

    export XDEBUG_CONFIG="idekey='phpstorm-xdebug'"

    if [ -z "${PROJECT_DIR}" ];
    then
        export PROJECT_DIR='/var/www/devobs'
    fi

    local rabbitmq_output_log="app/logs/rabbitmq."${NAMESPACE}".out.log"
    local rabbitmq_error_log="app/logs/rabbitmq."${NAMESPACE}".error.log"
    ensure_log_files_exist "${rabbitmq_output_log}" "${rabbitmq_error_log}"
    rabbitmq_output_log="${PROJECT_DIR}/${rabbitmq_output_log}"
    rabbitmq_error_log="${PROJECT_DIR}/${rabbitmq_error_log}"

    if [ -z "${username}" ];
    then
        echo 'Please export a valid username: export username="bob"'

        return
    fi

    local php_command='app/console weaving_the_web:amqp:produce:lists_members --screen_name='"${username}"

    local symfony_environment="$(get_symfony_environment)"

    if [ -z "${DOCKER_MODE}" ];
    then
        command="${symfony_environment} /usr/bin/php $PROJECT_DIR/${php_command}"
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

function produce_amqp_messages_from_member_timeline {
    export NAMESPACE="produce_messages_from_member_timeline"
    make remove-php-container

    export XDEBUG_CONFIG="idekey='phpstorm-xdebug'"

    if [ -z "${PROJECT_DIR}" ];
    then
        export PROJECT_DIR='/var/www/devobs'
    fi

    local rabbitmq_output_log="app/logs/rabbitmq."${NAMESPACE}".out.log"
    local rabbitmq_error_log="app/logs/rabbitmq."${NAMESPACE}".error.log"
    ensure_log_files_exist "${rabbitmq_output_log}" "${rabbitmq_error_log}"
    rabbitmq_output_log="${PROJECT_DIR}/${rabbitmq_output_log}"
    rabbitmq_error_log="${PROJECT_DIR}/${rabbitmq_error_log}"

    if [ -z "${username}" ];
    then
        echo 'Please export a valid username: export username="bob"'

        return
    fi

    local php_command='app/console weaving_the_web:amqp:produce:user_timeline --screen_name="'"${username}"'" -vvv'

    local symfony_environment="$(get_symfony_environment)"

    if [ -z "${DOCKER_MODE}" ];
    then
        command="${symfony_environment} /usr/bin/php $PROJECT_DIR/${php_command}"
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

function produce_amqp_messages_for_news_list {
    export NAMESPACE="produce_news_messages"
    make remove-php-container

    export XDEBUG_CONFIG="idekey='phpstorm-xdebug'"

    if [ -z "${PROJECT_DIR}" ];
    then
        export PROJECT_DIR='/var/www/devobs'
    fi

    local rabbitmq_output_log="app/logs/rabbitmq."${NAMESPACE}".out.log"
    local rabbitmq_error_log="app/logs/rabbitmq."${NAMESPACE}".error.log"
    ensure_log_files_exist "${rabbitmq_output_log}" "${rabbitmq_error_log}"
    rabbitmq_output_log="${PROJECT_DIR}/${rabbitmq_output_log}"
    rabbitmq_error_log="${PROJECT_DIR}/${rabbitmq_error_log}"

    if [ -z "${username}" ];
    then
        echo 'Please export a valid username: export username="bob"'

        return
    fi

    if [ -z "${list_name}" ];
    then
        echo 'Please export a valid list_name: export list_name="news :: France"'

        return
    fi

    local php_command='app/console weaving_the_web:amqp:produce:lists_members --screen_name='"${username}"' --list="'"${list_name}"'"'

    local symfony_environment="$(get_symfony_environment)"

    if [ -z "${DOCKER_MODE}" ];
    then
        command="${symfony_environment} /usr/bin/php $PROJECT_DIR/${php_command}"
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

function today_statuses {
    cat app/logs/dev.log | awk '{$1=$2=$3="";print $0}' | sed -e 's/^\s\+//' | grep `date -I` | awk '{$1=$2="";print $0}'
}

function follow_today_statuses {
    tail -f app/logs/dev.log | awk '{$1=$2=$3="";print $0}' | sed -e 's/^\s\+//' | grep `date -I` | awk '{$1=$2="";print $0}'
}
