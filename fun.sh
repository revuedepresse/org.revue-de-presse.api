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
    local WORKER_UID
    local WORKER_GID

    _set_up_configuration_files

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

function remove_container_image() {
    local container_name
    container_name="${1}"

    if [ -z "${container_name}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' '1st argument' 'container name' $'\n'

        return 1

    fi

    local DEBUG

    source ./.env.local

    docker ps -a |
        \grep "${COMPOSE_PROJECT_NAME}" |
        \grep "${container_name}" |
        awk '{print $1}' |
        xargs -I{} docker rm -f {}

    if [ -n "${DEBUG}" ];
    then
        docker images -a |
            \grep "${COMPOSE_PROJECT_NAME}" |
            \grep "${container_name}" |
            awk '{print $3}' |
            xargs -I{} docker rmi {}

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

    remove_container_image 'app'
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

function install() {
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

    cache_clear_warmup --reuse-existing-container
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

function start_database() {
    remove_container_image 'database'

    local command
    command=$(cat <<-SCRIPT
docker compose \
      --file=./provisioning/containers/docker-compose.yaml \
      --file=./provisioning/containers/docker-compose.override.yaml \
			up \
			--detach \
			database
SCRIPT
)

    echo 'About to execute "'"${command}"'"'
    /bin/bash -c "${command}"
}

function stop() {
    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        down
}

function run_php_unit_tests() {
    export SYMFONY_DEPRECATIONS_HELPER=disabled

    if [ -z ${DEBUG} ];
    then
        bin/phpunit -c ./phpunit.xml.dist --process-isolation --stop-on-failure --stop-on-error
        return
    fi

    bin/phpunit -c ./phpunit.xml.dist --verbose --debug --stop-on-failure --stop-on-error
}

set +Eeuo pipefail
