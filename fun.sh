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

function clear_cache() {
    load_configuration_parameters

    export COMPOSE_PROJECT_NAME

    printf '%s.%s' 'About to flush Redis cache' $'\n'

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        exec \
        cache \
        redis-cli FLUSHALL

    printf '%s.%s' 'Finished flushing Redis cache' $'\n'
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

    docker network inspect org-revue-de-presse >/dev/null 2>&1 || \
    docker network create org-revue-de-presse

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

function source_bench_env() {
    # run_bench_* runs in test context. Source .env.local first so the docker
    # compose CLI can resolve COMPOSE_PROJECT_NAME (test env files do not
    # declare it), then .env.test on top so test-mode values (APP_ENV=test,
    # BENCHMARK_HOST, API_AUTH_TOKEN, etc.) take precedence on overlap.
    set -a
    if [ -f ./.env.local ]; then
        source ./.env.local
    fi
    if [ -f ./.env.test ]; then
        source ./.env.test
    fi
    set +a
}

function run_bench_deps() {
    if [ -x bin/phpunit ] && [ -d vendor/phpunit/phpunit ] && [ -d vendor/symfony/phpunit-bridge ]; then
        return 0
    fi

    printf '→ Installing composer dev dependencies via the '\''app'\'' service container...%s' $'\n'

    source_bench_env

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        run --rm --no-deps --user root -T app \
        composer install --no-interaction --prefer-dist

    if [ ! -x bin/phpunit ]; then
        printf '✗ bin/phpunit still missing after composer install — aborting%s' $'\n' 1>&2
        return 1
    fi
}

function run_bench_highlights() {
    local bypass_cache="${1:-0}"

    run_bench_deps || return 1

    SYMFONY_DEPRECATIONS_HELPER='disabled' \
        BENCH_BYPASS_CACHE="${bypass_cache}" \
        BENCH_ITERATIONS="${BENCH_ITERATIONS:-200}" \
        BENCH_CONCURRENCY="${BENCH_CONCURRENCY:-1}" \
        BENCH_WARMUP="${BENCH_WARMUP:-3}" \
        BENCH_TIMEOUT="${BENCH_TIMEOUT:-30}" \
        php -d memory_limit="${BENCH_MEMORY_LIMIT:-1G}" \
            bin/phpunit -c ./phpunit.xml.dist --group performance --filter HighlightsPerformanceTest
}

function run_bench_with_redis() {
    run_bench_highlights 0
}

function run_bench_without_redis() {
    run_bench_highlights 1
}

function run_php_worker_build() {
    /bin/bash -c 'set -a; source ./.env.local; set +a; \
        docker compose \
            -f ./provisioning/containers/docker-compose.yaml \
            -f ./provisioning/containers/docker-compose.override.yaml \
            --profile frankenphp \
            build php-worker'
}

function run_php_worker_start() {
    /bin/bash -c 'set -a; source ./.env.local; set +a; \
        docker compose \
            -f ./provisioning/containers/docker-compose.yaml \
            -f ./provisioning/containers/docker-compose.override.yaml \
            --profile frankenphp \
            up --detach php-worker'
}

function run_php_worker_stop() {
    /bin/bash -c 'set -a; source ./.env.local; set +a; \
        docker compose \
            -f ./provisioning/containers/docker-compose.yaml \
            -f ./provisioning/containers/docker-compose.override.yaml \
            --profile frankenphp \
            stop php-worker'
}

function run_php_worker_logs() {
    /bin/bash -c 'set -a; source ./.env.local; set +a; \
        docker compose \
            -f ./provisioning/containers/docker-compose.yaml \
            -f ./provisioning/containers/docker-compose.override.yaml \
            --profile frankenphp \
            logs --follow --tail=200 php-worker'
}

function run_reverse_proxy_build() {
    # Traefik has no local build context — `build` would be a no-op.
    # `pull` actually fetches the published image so a subsequent
    # `run_reverse_proxy_start` doesn't have to.
    /bin/bash -c 'set -a; source ./.env.local; set +a; \
        docker compose \
            -f ./provisioning/containers/docker-compose.yaml \
            -f ./provisioning/containers/docker-compose.override.yaml \
            --profile frankenphp \
            pull reverse-proxy'
}

function run_reverse_proxy_start() {
    /bin/bash -c 'set -a; source ./.env.local; set +a; \
        docker compose \
            -f ./provisioning/containers/docker-compose.yaml \
            -f ./provisioning/containers/docker-compose.override.yaml \
            --profile frankenphp \
            up --detach reverse-proxy'
}

function run_reverse_proxy_stop() {
    /bin/bash -c 'set -a; source ./.env.local; set +a; \
        docker compose \
            -f ./provisioning/containers/docker-compose.yaml \
            -f ./provisioning/containers/docker-compose.override.yaml \
            --profile frankenphp \
            stop reverse-proxy'
}

function run_update_version() {
    local repo_file
    local source_tag
    local latest
    local current

    repo_file='src/Trends/Infrastructure/Repository/PopularPublicationRepository.php'

    # When TAG is unset (e.g. plain `make update-version`), default it to the
    # next minor on top of the most recent existing tag in the same track:
    #
    #   latest tag: v5.1.0-http-api  →  TAG defaults to v5.2.0-http-api
    #   latest tag: v18.8            →  TAG defaults to v18.9.0
    #
    # The function then proceeds as if TAG had been supplied explicitly:
    # update the PHP file's version literal AND create the git tag on HEAD.
    if [ -z "${TAG:-}" ]; then
        local latest_tag version_part track_suffix major minor
        latest_tag=$(git describe --tags --abbrev=0 2>/dev/null)
        if [ -z "${latest_tag}" ]; then
            printf '%s%s' 'ERROR: no existing tag to derive next minor from' $'\n' 1>&2
            return 1
        fi
        if ! printf '%s' "${latest_tag}" | grep -qE '^v[0-9]+\.[0-9]+'; then
            printf 'ERROR: latest tag %s does not match v<major>.<minor> prefix%s' "${latest_tag}" $'\n' 1>&2
            return 1
        fi
        version_part=$(printf '%s' "${latest_tag}" | sed -E 's/^(v[0-9]+(\.[0-9]+){1,2}).*/\1/')
        track_suffix=${latest_tag#"${version_part}"}
        major=$(printf '%s' "${version_part}" | sed -E 's/^v([0-9]+).*/\1/')
        minor=$(printf '%s' "${version_part}" | sed -E 's/^v[0-9]+\.([0-9]+).*/\1/')
        TAG="v${major}.$((minor + 1)).0${track_suffix}"
        printf '→ TAG unset; defaulting to next minor: %s (based on latest tag %s)%s' "${TAG}" "${latest_tag}" $'\n'
    fi
    source_tag="${TAG}"

    if ! printf '%s' "${source_tag}" | grep -qE '^v[0-9]+(\.[0-9]+){1,2}'; then
        printf 'ERROR: %s does not match expected v<major>.<minor>(.<patch>) prefix%s' "${source_tag}" $'\n' 1>&2
        return 1
    fi
    latest=$(printf '%s' "${source_tag}" | sed -E 's/^(v[0-9]+(\.[0-9]+){1,2}).*/\1/')

    current=$(sed -nE "s/^[[:space:]]+'version' => '([^']+)',?\$/\1/p" "${repo_file}" | head -1)
    if [ -z "${current}" ]; then
        printf "ERROR: 'version' key not found in %s%s" "${repo_file}" $'\n' 1>&2
        return 1
    fi

    if [ "${current}" = "${latest}" ]; then
        printf '%s version already up-to-date: %s%s' "${repo_file}" "${current}" $'\n'
    else
        # Refuse to proceed if the index has unrelated staged changes —
        # otherwise our `git commit` would sweep them into the release commit.
        if ! git diff --cached --quiet; then
            printf 'ERROR: index has already-staged changes; unstage them before update-version%s' $'\n' 1>&2
            return 1
        fi

        sed -i.bak -E "s|('version' => ')[^']+(',)|\1${latest}\2|" "${repo_file}"
        rm -f "${repo_file}.bak"
        printf '%s version updated: %s -> %s%s' "${repo_file}" "${current}" "${latest}" $'\n'

        # Stage and commit the version bump so the new tag points at this
        # specific change rather than whatever HEAD happened to be. Signed
        # commit, matching the project's PGP convention.
        if ! git add "${repo_file}"; then
            printf 'ERROR: git add %s failed%s' "${repo_file}" $'\n' 1>&2
            return 1
        fi
        if ! git commit -S -m "Release ${TAG}"; then
            printf 'ERROR: git commit failed; staged %s edit is left for manual handling%s' "${repo_file}" $'\n' 1>&2
            return 1
        fi
        printf '✓ Committed release bump (HEAD now carries version %s)%s' "${latest}" $'\n'
    fi

    # Create the git tag on HEAD. Signed annotated tag, matching the
    # PGP-signed commits convention. Idempotent: skip if a tag with this
    # exact name already points somewhere.
    if git rev-parse --verify --quiet "refs/tags/${TAG}" >/dev/null; then
        printf '→ git tag %s already exists, skipping creation%s' "${TAG}" $'\n'
    else
        printf '→ git tag -s %s%s' "${TAG}" $'\n'
        git tag -s "${TAG}" -m "Release ${TAG}"
    fi
}

function run_reverse_proxy_password() {
    local user="${1:-admin}"
    local password
    local hash
    local htpasswd_file='./var/etc/letsencrypt/htpasswd'

    # 24-char URL-safe random password — plenty for a local dashboard.
    password=$(openssl rand -base64 32 | tr -d '\n=+/' | head -c 24)

    if command -v htpasswd >/dev/null 2>&1; then
        hash=$(htpasswd -nbB "${user}" "${password}" | tr -d '\n')
    else
        # Fall back to the httpd image — a few-MB pull, runs offline after.
        hash=$(docker run --rm httpd:2.4-alpine \
            htpasswd -nbB "${user}" "${password}" | tr -d '\n')
    fi

    if [ -z "${hash}" ]; then
        printf '✗ Failed to generate htpasswd hash%s' $'\n' 1>&2
        return 1
    fi

    # Single-user dashboard — overwrite the file so re-running the target
    # rotates credentials cleanly. Edit the file by hand if you want
    # multiple users (one user:hash line each).
    mkdir -p "$(dirname "${htpasswd_file}")"
    printf '%s\n' "${hash}" > "${htpasswd_file}"

    printf '%s%s' '✓ Wrote new Traefik dashboard credentials to '"${htpasswd_file}" $'\n'
    printf '  %-9s %s%s' 'user:'     "${user}"     $'\n'
    printf '  %-9s %s%s' 'password:' "${password}" $'\n'
    printf '%s%s' '  (save the password somewhere — only the bcrypt hash is stored on disk)' $'\n'
    printf '%s%s' '' $'\n'
    printf '%s%s' '→ Traefik picks up the new file automatically (file provider watch=true).' $'\n'
}

function run_php_unit_tests() {
    export SYMFONY_DEPRECATIONS_HELPER='disabled'

    if [ -z "${DEBUG}" ];
    then

        bin/phpunit -c ./phpunit.xml.dist --stop-on-failure --stop-on-error

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
      --env-file ./.env.local \
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

    # Stop only the FPM-stack services. `cache` is a baseline service
    # shared with the opt-in php-worker / reverse-proxy stack — wiping
    # the project with `down` would kill Redis for them too, leaving the
    # benchmark harness in a degraded "cache=error" state. Naming the
    # FPM services explicitly here keeps the two stacks independent.
    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        stop app service
}

function validate_docker_compose_configuration() {
    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        config -q
}

set +Eeuo pipefail
