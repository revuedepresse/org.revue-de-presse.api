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
} >> ./var/log/build.log 2>> ./var/log/build.error.log

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
} >> ./var/log/build.log 2>> ./var/log/build.error.log

function build() {
    local WORKER
    local WORKER_UID
    local WORKER_GID

    _set_up_configuration_files

    if [ -z "${WORKER}" ];
    then

      printf 'A %s is expected as %s ("%s").%s' 'non-empty string' 'worker name' 'WORKER' $'\n'

      return 1

    fi

    if [ -z "${WORKER_UID}" ];
    then

      printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user uid' 'WORKER_UID' $'\n'

      return 1

    fi

    if [ -z "${WORKER_GID}" ];
    then

      printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user gid' 'WORKER_GID' $'\n'

      return 1

    fi

    docker compose \
        --file=./provisioning/containers/docker-compose.yaml \
        --file=./provisioning/containers/docker-compose.override.yaml \
        build \
        --build-arg "WORKER_UID=${WORKER_UID}" \
        --build-arg "WORKER_GID=${WORKER_GID}" \
        --build-arg "WORKER=${WORKER}" \
        app \
        process-manager \
        worker
} >> ./var/log/build.log 2>> ./var/log/build.error.log

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
        app

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        exec \
        --env _WORKER="${WORKER}" \
        -T app \
        /bin/bash -c '. ./bin/console.sh && dispatch_fetch_publications_messages'
}

function guard_against_missing_variables() {
    if [ -z "${WORKER}" ];
    then

        printf 'A %s is expected as %s ("%s" environment variable).%s' 'non-empty string' 'worker name e.g. worker.example.com' 'WORKER' $'\n'

        exit 1

    fi

    if [ "${WORKER}" = 'worker.example.org' ];
    then

        printf 'Have you picked a satisfying worker name ("%s" environment variable - "%s" as default value is not accepted).%s' 'WORKER' 'worker.example.org' $'\n'

        exit 1

    fi
} >> ./var/log/build.log 2>> ./var/log/build.error.log

function remove_container_image() {
    local container_name
    container_name="${1}"

    if [ -z "${container_name}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' '1st argument' 'container name' $'\n'

        return 1

    fi

    local DEBUG
    local WORKER_UID
    local WORKER_GID
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
} >> ./var/log/build.log 2>> ./var/log/build.error.log

function clean() {
    local temporary_directory
    temporary_directory="${1}"

    if [ -n "${temporary_directory}" ];
    then
        printf 'About to remove "%s".%s' "${temporary_directory}" $'\n'

        _set_file_permissions "${temporary_directory}"

        return 0
    fi

    remove_container_image 'app'
    remove_container_image 'process-manager'
    remove_container_image 'worker'
} >> ./var/log/build.log 2>> ./var/log/build.error.log

function clear_cache_warmup() {
    local WORKER_UID
    local WORKER_GID
    local WORKER

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
        /bin/bash -c '. /scripts/clear-app-cache.sh'

    clean ''
} >> ./var/log/build.log 2>> ./var/log/build.error.log

function install() {
    guard_against_missing_variables

    local WORKER
    local WORKER_UID
    local WORKER_GID

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
        --env _WORKER="${WORKER}" \
        --user root \
        -T app \
        /bin/bash -c 'source /scripts/install-app-requirements.sh'

    clear_cache_warmup --reuse-existing-container
} >> ./var/log/build.log 2>> ./var/log/build.error.log

function run_unit_tests() {
    export SYMFONY_DEPRECATIONS_HELPER='disabled'

    if [ -z ${DEBUG} ];
    then
        bin/phpunit -c ./phpunit.xml.dist --process-isolation --stop-on-failure --stop-on-error
        return
    fi

    bin/phpunit -c ./phpunit.xml.dist --verbose --debug --stop-on-failure --stop-on-error
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

    remove_container_image 'process-manager'

    local command
    command=$(cat <<-SCRIPT
docker compose \
      --file=./provisioning/containers/docker-compose.yaml \
      --file=./provisioning/containers/docker-compose.override.yaml \
			up \
			--detach \
			--no-recreate \
			process-manager
SCRIPT
)

    echo 'About to execute "'"${command}"'"'
    /bin/bash -c "${command}"
} >> ./var/log/build.log 2>> ./var/log/build.error.log

function start_database() {
    guard_against_missing_variables

    remove_container_image 'database'

    local command
    command=$(cat <<-SCRIPT
docker compose \
      --file=./provisioning/containers/docker-compose.yaml \
      --file=./provisioning/containers/docker-compose.override.yaml \
			up \
			--detach \
			--force-recreate \
			database
SCRIPT
)

    echo 'About to execute "'"${command}"'"'
    /bin/bash -c "${command}"
} >> ./var/log/build.log 2>> ./var/log/build.error.log

function stop() {
    guard_against_missing_variables

    remove_container_image 'app'
    remove_container_image 'process-manager'
    remove_container_image 'worker'
} >> ./var/log/build.log 2>> ./var/log/build.error.log

function stop_database() {
    guard_against_missing_variables

    remove_container_image 'database'
} >> ./var/log/build.log 2>> ./var/log/build.error.log

function validate_docker_compose_configuration() {
    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        config -q
} >> ./var/log/build.log 2>> ./var/log/build.error.log

set +Eeuo pipefail
