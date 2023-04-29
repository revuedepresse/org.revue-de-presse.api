#!/usr/bin/env bash
set -Eeuo pipefail

trap "exit 1" TERM
export service_pid=$$

function build() {
    local DEBUG
    local PROJECT
    local PROJECT_OWNER_UID
    local PROJECT_OWNER_GID

    load_configuration_parameters

    if [ -n "${DEBUG}" ];
    then

        docker compose \
            --file=./provisioning/containers/docker-compose.yaml \
            --file=./provisioning/containers/docker-compose.override.yaml \
            build \
            --no-cache \
            --build-arg "OWNER_UID=${PROJECT_OWNER_UID}" \
            --build-arg "OWNER_GID=${PROJECT_OWNER_GID}" \
            --build-arg "PROJECT=${PROJECT}" \
            app \
            cache \
            service

    else

        docker compose \
            --file=./provisioning/containers/docker-compose.yaml \
            --file=./provisioning/containers/docker-compose.override.yaml \
            build \
            --build-arg "OWNER_UID=${PROJECT_OWNER_UID}" \
            --build-arg "OWNER_GID=${PROJECT_OWNER_GID}" \
            --build-arg "PROJECT=${PROJECT}" \
            app \
            cache \
            service

      fi
}

function clean() {
    local temporary_directory
    temporary_directory="${1}"

    if [ -n "${temporary_directory}" ];
    then

        printf 'About to revise file permissions for "%s" before clean up.%s' "${temporary_directory}" $'\n'

        set_file_permissions "${temporary_directory}"

        return 0

    fi

    remove_running_container_and_image_in_debug_mode 'app'
}

function clear_cache_warmup() {
    local PROJECT_OWNER_UID
    local PROJECT_OWNER_GID

    load_configuration_parameters

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
        --user "${PROJECT_OWNER_UID}:${PROJECT_OWNER_GID}" \
        app \
        /bin/bash -c '. /scripts/clear-app-cache.sh'
}

function guard_against_missing_variables() {
    if [ -z "${COMPOSE_PROJECT_NAME}" ];
    then

        printf 'A %s is expected as %s ("%s" environment variable).%s' 'non-empty string' 'project name' 'COMPOSE_PROJECT_NAME' $'\n'

        kill -s TERM $service_pid

        return 1

    fi

    if [ -z "${PROJECT}" ];
    then

        printf 'A %s is expected as %s ("%s" environment variable).%s' 'non-empty string' 'service name e.g. org.example.api' 'PROJECT' $'\n'

        kill -s TERM $service_pid

        return 1

    fi

    if [ "${PROJECT}" = 'org.example.api' ];
    then

        printf 'Have you picked a satisfying worker name ("%s" environment variable - "%s" as default value is not accepted).%s' 'PROJECT' 'org.example.api' $'\n'

        kill -s TERM $service_pid

        return 1

    fi

    if [ -z "${PROJECT_OWNER_UID}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user uid' 'PROJECT_OWNER_UID' $'\n'

        kill -s TERM $service_pid

        return 1

    fi

    if [ -z "${PROJECT_OWNER_GID}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user gid' 'PROJECT_OWNER_GID' $'\n'

        kill -s TERM $service_pid

        return 1

    fi
}

function green() {
    echo -n "\e[32m"
}

function install() {
    local PROJECT
    local PROJECT_OWNER_UID
    local PROJECT_OWNER_GID

    load_configuration_parameters

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        up \
        --force-recreate \
        --detach \
        app

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        exec \
        --user root \
        -T app \
        /bin/bash -c 'source /scripts/install-app-requirements.sh'

    clear_cache_warmup --reuse-existing-container
}

function load_configuration_parameters() {
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

    guard_against_missing_variables

    printf '%s'           $'\n'
    printf '%b%s%b"%s"%s' "$(green)" 'COMPOSE_PROJECT_NAME: ' "$(reset_color)" "${COMPOSE_PROJECT_NAME}" $'\n'
    printf '%b%s%b"%s"%s' "$(green)" 'DEBUG:                ' "$(reset_color)" "${DEBUG}" $'\n'
    printf '%b%s%b"%s"%s' "$(green)" 'PROJECT:              ' "$(reset_color)" "${PROJECT}" $'\n'
    printf '%b%s%b"%s"%s' "$(green)" 'PROJECT_OWNER_UID:    ' "$(reset_color)" "${PROJECT_OWNER_UID}" $'\n'
    printf '%b%s%b"%s"%s' "$(green)" 'PROJECT_OWNER_GID:    ' "$(reset_color)" "${PROJECT_OWNER_GID}" $'\n'
    printf '%s'           $'\n'
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
    local PROJECT_OWNER_UID
    local PROJECT_OWNER_GID
    local PROJECT

    load_configuration_parameters

    docker ps -a |
        \grep "${COMPOSE_PROJECT_NAME}" |
        \grep "${container_name}" |
        awk '{print $1}' |
        xargs -I{} docker rm -f {}
}

function reset_color() {
    echo -n $'\033'\[00m
}

function run_php_unit_tests() {
    export SYMFONY_DEPRECATIONS_HELPER='disabled'

    if [ -z "${DEBUG}" ];
    then

        bin/phpunit -c ./phpunit.xml.dist --process-isolation --stop-on-failure --stop-on-error

        return

    fi

    bin/phpunit -c ./phpunit.xml.dist --verbose --debug --stop-on-failure --stop-on-error
}

function set_file_permissions() {
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

function start() {
    remove_running_container_and_image_in_debug_mode 'app'
    load_configuration_parameters

    local command
    command=$(cat <<-SCRIPT
docker compose \
      --file=./provisioning/containers/docker-compose.yaml \
      --file=./provisioning/containers/docker-compose.override.yaml \
			up \
			--force-recreate \
			--detach \
			service
SCRIPT
)

    echo 'About to execute "'"${command}"'"'
    /bin/bash -c "${command}"
}

function stop() {
    load_configuration_parameters

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        down
}

function validate_docker_compose_configuration() {
    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        config -q
}

set +Eeuo pipefail
