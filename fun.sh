#!/usr/bin/env bash
set -Eeuo pipefail

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

    validate_docker_compose_configuration

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
    local WORKER
    local WORKER_OWNER_UID
    local WORKER_OWNER_GID

    _set_up_configuration_files

    if [ -z "${WORKER}" ];
    then

      printf 'A %s is expected as %s ("%s").%s' 'non-empty string' 'worker name' 'WORKER' $'\n'

      return 1

    fi

    if [ -z "${WORKER_OWNER_UID}" ];
    then

      printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user uid' 'WORKER_OWNER_UID' $'\n'

      return 1

    fi

    if [ -z "${WORKER_OWNER_GID}" ];
    then

      printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user gid' 'WORKER_OWNER_GID' $'\n'

      return 1

    fi

    docker compose \
        --file=./provisioning/containers/docker-compose.yaml \
        --file=./provisioning/containers/docker-compose.override.yaml \
        build \
        --build-arg "WORKER_DIR=${WORKER}" \
        --build-arg "WORKER_OWNER_UID=${WORKER_OWNER_UID}" \
        --build-arg "WORKER_OWNER_GID=${WORKER_OWNER_GID}" \
        app \
        process-manager \
        worker
}

function dispatch_amqp_messages() {
    local USERNAME
    local LIST_NAME

    _set_up_configuration_files

    if [ -z "${USERNAME}" ];
    then

        printf 'A %s is expected as %s ("%s" environment variable).%s' 'non-empty string' 'member screen name' 'USERNAME' $'\n'

        return 1

    fi

    if [ -z "${LIST_NAME}" ];
    then

        printf 'A %s is expected as %s ("%s" environment variable).%s' 'non-empty string' 'publisher list' 'LIST_NAME' $'\n'

        return 1

    fi

    clean ''

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        up \
        --detach \
        --no-recreate \
        worker

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        exec \
        --env WORKER="${WORKER}" \
        -T worker \
        /bin/bash -c '. ./bin/console.sh && dispatch_fetch_publications_messages'
}

function guard_against_missing_variables() {
    if [ -z "${WORKER}" ];
    then

        printf 'A %s is expected as %s ("%s" environment variable).%s' 'non-empty string' 'worker name e.g. worker.example.org' 'WORKER' $'\n'

        exit 1

    fi
}

function remove_running_container_and_image_in_debug_mode() {
    local container_name
    container_name="${1}"

    if [ -z "${container_name}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' '1st argument' 'container name' $'\n'

        return 1

    fi

    local DEBUG
    local WORKER_OWNER_UID
    local WORKER_OWNER_GID
    local WORKER

    _set_up_configuration_files

    local project_name

    if [ -n "${COMPOSE_PROJECT_NAME}" ];
    then
        project_name="${COMPOSE_PROJECT_NAME}"
    else
        project_name="$(get_project_name)"
    fi

    docker ps -a |
        \grep "${project_name}" |
        \grep "${container_name}" |
        awk '{print $1}' |
        xargs -I{} docker rm -f {}

    if [ -n "${DEBUG}" ];
    then
        docker images -a |
            \grep "${project_name}" |
            \grep "${container_name}" |
            awk '{print $3}' |
            xargs -I{} docker rmi -f {}

        build
    fi
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

    remove_running_container_and_image_in_debug_mode 'app'
    remove_running_container_and_image_in_debug_mode 'worker'
}

function clear_cache_warmup() {
    local WORKER_OWNER_UID
    local WORKER_OWNER_GID
    local WORKER

    _set_up_configuration_files

    local reuse_existing_container
    reuse_existing_container="${1}"

    if [ -z "${reuse_existing_container}" ];
    then
        remove_running_container_and_image_in_debug_mode 'app'

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
        --user "${WORKER_OWNER_UID}:${WORKER_OWNER_GID}" \
        app \
        /bin/bash -c '. /scripts/clear-app-cache.sh'

    clean ''
}

function install() {
    guard_against_missing_variables

    local WORKER
    local WORKER_OWNER_UID
    local WORKER_OWNER_GID

    _set_up_configuration_files

    clean ''

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        up \
        --detach \
        --no-recreate \
        app

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        exec \
        --env WORKER="${WORKER}" \
        --user root \
        -T app \
        /bin/bash -c 'source /scripts/install-app-requirements.sh'

    clear_cache_warmup --reuse-existing-container
}

function run_unit_tests() {
    export SYMFONY_DEPRECATIONS_HELPER='disabled'

    if [ -z ${DEBUG} ];
    then
        bin/phpunit -c ./phpunit.xml.dist \
        --process-isolation \
        --stop-on-failure \
        --stop-on-error

        return
    fi

    bin/phpunit -c ./phpunit.xml.dist \
    --debug \
    --stop-on-failure \
    --stop-on-error \
    --verbose
}

function get_project_name() {
    local project_name
    project_name="$(
        docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        config --format json \
        | jq '.name' \
        | tr -d '"'
    )"

    echo "${project_name}"
}

function get_process_manager_shell() {
    if ! command -v jq >> /dev/null 2>&1;
    then
        printf 'Is %s (%s) installed?%s' 'command-line JSON processor' 'jq' $'\n'

        return 1
    fi

    local project_name
    project_name="$(get_project_name)"

    docker exec -ti "$(
        docker ps -a \
        | \grep "${project_name}" \
        | \grep 'process\-manager' \
        | awk '{print $1}'
    )" bash
}

function get_worker_shell() {
    if ! command -v jq >> /dev/null 2>&1;
    then
        printf 'Is %s (%s) installed?%s' 'command-line JSON processor' 'jq' $'\n'

        return 1
    fi

    local project_name
    project_name="$(get_project_name)"

    docker exec -ti "$(
        docker ps -a \
        | \grep "${project_name}" \
        | \grep 'worker' \
        | awk '{print $1}'
    )" bash
}

function start() {
    guard_against_missing_variables

    clean ''

    remove_running_container_and_image_in_debug_mode 'process-manager'

    local command
    command=$(cat <<-SCRIPT
docker compose \
      --file=./provisioning/containers/docker-compose.yaml \
      --file=./provisioning/containers/docker-compose.override.yaml \
			up \
			--detach \
			--no-recreate \
			amqp
SCRIPT
)

    local command
    command=$(cat <<-SCRIPT
docker compose \
      --file=./provisioning/containers/docker-compose.yaml \
      --file=./provisioning/containers/docker-compose.override.yaml \
			up \
			--detach \
			--force-recreate \
			process-manager
SCRIPT
)

    echo 'About to execute "'"${command}"'"'
    /bin/bash -c "${command}"
}

function start_amqp_broker() {
    guard_against_missing_variables

    local command
    command=$(cat <<-SCRIPT
docker compose \
      --file=./provisioning/containers/docker-compose.yaml \
      --file=./provisioning/containers/docker-compose.override.yaml \
			up \
			--detach \
			--no-recreate \
			amqp
SCRIPT
)

    echo 'About to execute "'"${command}"'"'
    /bin/bash -c "${command}"
}

function start_database() {
    guard_against_missing_variables

    local command
    command=$(cat <<-SCRIPT
docker compose \
      --file=./provisioning/containers/docker-compose.yaml \
      --file=./provisioning/containers/docker-compose.override.yaml \
			up \
			--detach \
			--no-recreate \
			database
SCRIPT
)

    echo 'About to execute "'"${command}"'"'
    /bin/bash -c "${command}"
}

function stop() {
    guard_against_missing_variables

    remove_running_container_and_image_in_debug_mode 'app'
    remove_running_container_and_image_in_debug_mode 'process-manager'
    remove_running_container_and_image_in_debug_mode 'worker'
}

function stop_amqp_broker() {
    guard_against_missing_variables

    remove_running_container_and_image_in_debug_mode 'amqp'
}

function stop_database() {
    guard_against_missing_variables

    remove_running_container_and_image_in_debug_mode 'database'
}

function validate_docker_compose_configuration() {
    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        config -q
}

set +Eeuo pipefail
