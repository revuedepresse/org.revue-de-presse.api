#!/usr/bin/env bash
set -Eeuo pipefail

trap "exit 1" TERM
export service_pid=$$

function build() {
    local DEBUG
    local PROJECT
    local PROJECT_OWNER_UID
    local PROJECT_OWNER_GID
    local HOST_OS

    load_configuration_parameters
    HOST_OS="$(uname)"

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
            --build-arg "HOST_OS=${HOST_OS}" \
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
            --build-arg "HOST_OS=${HOST_OS}" \
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

    run_doctrine_migrations
    run_chat_store_setup
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

    if [ -n "${LABEL:-}" ];
    then

        export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME}_${LABEL}"

    fi

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

function run_doctrine_migrations() {
    local PROJECT
    local PROJECT_OWNER_UID
    local PROJECT_OWNER_GID

    load_configuration_parameters

    printf '%s.%s' 'About to apply pending Doctrine migrations' $'\n'

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        exec \
        --user "${PROJECT_OWNER_UID}:${PROJECT_OWNER_GID}" \
        -T app \
        /bin/bash -c 'bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration'

    printf '%s.%s' 'Finished applying Doctrine migrations' $'\n'
}

# Provision the pgvector-backed publication-embedding store owned by
# symfony/ai-store (table + HNSW index). Idempotent: re-running against
# an already-set-up store is a no-op. Skipped silently when the
# symfony/ai-bundle isn't loaded yet (pre-`composer install`).
function run_chat_store_setup() {
    local PROJECT
    local PROJECT_OWNER_UID
    local PROJECT_OWNER_GID

    load_configuration_parameters

    printf '%s.%s' 'About to provision the Chat pgvector store (ai:store:setup ai.store.postgres.chat_publications)' $'\n'

    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        exec \
        --user "${PROJECT_OWNER_UID}:${PROJECT_OWNER_GID}" \
        -T app \
        /bin/bash -c '
            if bin/console list ai 2>/dev/null | grep -q "ai:store:setup"; then
                bin/console ai:store:setup ai.store.postgres.chat_publications --no-interaction
            else
                printf "✓ skipped: ai:store:setup not registered yet (symfony/ai-bundle not installed)\n"
            fi
        '

    printf '%s.%s' 'Finished provisioning the Chat pgvector store' $'\n'
}

# Drop + recreate the pgvector store from its current ai.yaml schema. Use
# whenever the column type changes (e.g. switching the embedding model
# dimension or vector_type: vector → halfvec). `setup` alone uses
# `CREATE TABLE IF NOT EXISTS` and silently keeps the old schema.
function run_chat_store_reset() {
    # `chat-store-setup` targets `docker compose exec app`, but the always-
    # running container hosting bin/console is `service` (named e.g.
    # org_revue-de-presse_api-service-1). Discover it via the same regex
    # `run_chat_embed_snapshots` uses so this works regardless of which
    # compose services are up.
    local container
    container=$(_chat_api_container)
    if [ -z "${container}" ]; then
        printf '✗ No running api-service container; run "make start" first.%s' $'\n' 1>&2
        return 1
    fi

    printf '→ Dropping + recreating the Chat pgvector store (destructive — all embeddings wiped)…%s' $'\n'

    docker exec "${container}" /bin/bash -c '
        set -e
        if ! bin/console list ai 2>/dev/null | grep -q "ai:store:setup"; then
            printf "✗ ai:store:* not registered (symfony/ai-bundle missing)\n" 1>&2
            exit 1
        fi
        bin/console ai:store:drop  ai.store.postgres.chat_publications --force --no-interaction
        bin/console ai:store:setup ai.store.postgres.chat_publications --no-interaction
    ' || {
        printf '✗ Chat pgvector store reset FAILED — see error above.%s' $'\n' 1>&2
        return 1
    }

    printf '✓ Chat pgvector store reset to current ai.yaml schema.%s' $'\n'
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

    repo_file='config/packages/api_platform.yaml'

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

    current=$(sed -nE "s/^[[:space:]]+version:[[:space:]]*'([^']+)'[[:space:]]*\$/\1/p" "${repo_file}" | head -1)
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

        sed -i.bak -E "s|(^[[:space:]]+version:[[:space:]]*')[^']+(')|\1${latest}\2|" "${repo_file}"
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

function run_chat_jwt_secret() {
    local secret
    # 256-bit (32-byte) entropy, URL-safe base64 (no padding, no slashes).
    secret=$(openssl rand -base64 32 | tr -d '\n=+/' | head -c 43)

    if [ -z "${secret}" ]; then
        printf '✗ Failed to generate API_JWT_SECRET%s' $'\n' 1>&2
        return 1
    fi

    printf '%s%s' '✓ Generated a fresh API_JWT_SECRET (43 URL-safe chars, 256-bit entropy):' $'\n'
    printf '%s%s' '' $'\n'
    printf '  API_JWT_SECRET=%s%s' "${secret}" $'\n'
    printf '%s%s' '' $'\n'
    printf '%s%s' '→ Paste the SAME line into:' $'\n'
    printf '%s%s' '    1. org.revue-de-presse.api/.env.local      (the verifier)' $'\n'
    printf '%s%s' '    2. org.revue-de-presse.benchmark/nuxt/.env (the signer, local dev)' $'\n'
    printf '%s%s' '    3. Netlify env vars for the Nuxt site      (the signer, prod)' $'\n'
    printf '%s%s' '' $'\n'
    printf '%s%s' '⚠  Rotating invalidates every active chat session. Deploy API first, Nuxt second.' $'\n'
}

function _chat_embeddings_compose() {
    docker compose \
        -f ./provisioning/containers/docker-compose.yaml \
        -f ./provisioning/containers/docker-compose.override.yaml \
        --profile embeddings \
        "$@"
}

function run_chat_embeddings_build() {
    printf '→ Pulling the ollama image (idempotent; cached layers skip)…%s' $'\n'
    _chat_embeddings_compose pull ollama
    printf '✓ ollama image ready.%s' $'\n'
}

function run_chat_embeddings_start() {
    local model="${OLLAMA_EMBEDDING_MODEL:-bge-m3}"

    printf '→ Starting the ollama container (compose profile: embeddings)…%s' $'\n'
    _chat_embeddings_compose up -d ollama

    printf '→ Waiting for ollama to accept connections…%s' $'\n'
    local attempts=0
    until _chat_embeddings_compose exec -T ollama ollama list >/dev/null 2>&1
    do
        attempts=$((attempts + 1))
        if [ "${attempts}" -ge 30 ]; then
            printf '✗ ollama did not become ready within ~60s.%s' $'\n' 1>&2
            return 1
        fi
        sleep 2
    done

    # `ollama pull` is itself idempotent — it short-circuits with a
    # "already up-to-date" line if the model is already in the volume.
    printf '→ Ensuring embedding model "%s" is loaded (no-op if already present)…%s' "${model}" $'\n'
    _chat_embeddings_compose exec -T ollama ollama pull "${model}"

    printf '✓ ollama is up and "%s" is loaded.%s' "${model}" $'\n'
    printf '  Next: set OLLAMA_BASE_URL=http://ollama:11434 in .env.local (if not already), then run `make chat-store-setup` and `make chat-embed-snapshots`.%s' $'\n'
}

function run_chat_embeddings_stop() {
    printf '→ Stopping the ollama container (model volume preserved)…%s' $'\n'
    _chat_embeddings_compose stop ollama
    printf '✓ ollama stopped.%s' $'\n'
}

function run_chat_embeddings_shell() {
    _chat_embeddings_compose exec ollama /bin/sh
}

# Pull the chat-completion model into the same ollama container that
# already hosts the embedding model. Idempotent: `ollama pull` no-ops if
# the model is already cached in the named volume. The container itself
# is brought up by the `chat-embeddings-start` make prerequisite, so this
# function assumes ollama is already running.
function run_chat_completion_start() {
    # Defaults to gemma2:2b — fits a default Docker Desktop VM. Override
    # to gemma2:9b (and bump ollama mem_limit + Docker VM RAM) for
    # higher-quality answers on a beefier dev box.
    local model="${OLLAMA_COMPLETION_MODEL:-gemma2:2b}"

    printf '→ Ensuring chat-completion model "%s" is downloaded (no-op if cached)…%s' "${model}" $'\n'
    _chat_embeddings_compose exec -T ollama ollama pull "${model}"

    # Pre-load the model into RAM. Without this, the FIRST user question
    # pays a ~30-60s cold-load penalty before any token streams back —
    # which usually blows past PHP's max_execution_time and the user sees
    # `providers_exhausted` from the dropped SSE connection. Ollama then
    # keeps the model resident for ~5 minutes after the last call
    # (configurable via OLLAMA_KEEP_ALIVE on the container).
    printf '→ Warming up "%s" (loads weights into RAM; first user-turn latency drops from ~60s to ~1s)…%s' "${model}" $'\n'
    _chat_embeddings_compose exec -T ollama ollama run "${model}" 'Réponds uniquement "OK".' >/dev/null 2>&1 || \
        printf '⚠ warmup query failed — the model is downloaded but first chat turn will be slow.%s' $'\n' 1>&2

    printf '✓ "%s" ready and warm. ~6 GB resident; ~30-60s per turn on CPU after warmup.%s' "${model}" $'\n'
}

function _chat_api_container() {
    docker ps -a --format '{{.ID}} {{.Names}}' | awk '/api[-]service/ { print $1; exit }'
}

function run_chat_cache_clear() {
    local container
    container=$(_chat_api_container)
    if [ -z "${container}" ]; then
        printf '✗ No running api-service container; run "make start" first.%s' $'\n' 1>&2
        return 1
    fi
    printf '→ Wiping var/cache/dev in api-service (forces YAML re-read on next --no-debug run)…%s' $'\n'
    docker exec "${container}" rm -rf var/cache/dev
    printf '✓ Cache cleared.%s' $'\n'
}

function run_chat_embed_snapshots() {
    local args="${1:-}"
    # Delegate to bin/console inside the running app container so the command
    # sees the configured Doctrine connection, Redis cache, and Symfony AI bundle.
    local container
    container=$(_chat_api_container)
    if [ -z "${container}" ]; then
        printf '✗ No running api-service container; run "make start" first.%s' $'\n' 1>&2
        return 1
    fi

    # --no-debug skips Symfony's YAML resource-tracking. If config/packages/
    # has been edited since the cached container was compiled, the cache is
    # stale (e.g. still routes the vectorizer at Gemini after we swapped to
    # Ollama). Detect and clear, otherwise the embed run silently uses the
    # old wiring.
    local container_cache='var/cache/dev/App_KernelDevContainer.php'
    if docker exec "${container}" /bin/bash -c "[ -f ${container_cache} ] && [ -n \"\$(find config/packages -name '*.yaml' -newer ${container_cache} -print -quit)\" ]" 2>/dev/null; then
        printf '→ config/packages/*.yaml changed since the cache was built; clearing…%s' $'\n'
        docker exec "${container}" rm -rf var/cache/dev
    fi
    # --no-debug skips the AI bundle's DebugCompilerPass, which decorates
    # every platform/store with TraceablePlatform/TraceableStore. Those
    # decorators accumulate every API call (including full 3072-dim vector
    # payloads) in an internal $calls array for the profiler — fine for a
    # single HTTP request, fatal for a 449-day CLI backfill (OOM at ~134 MB).
    # Disabling debug drops the wrappers entirely.
    # shellcheck disable=SC2086
    docker exec -ti "${container}" bin/console --no-debug chat:embed-snapshots ${args}
}

# Internal: refuse to run on non-Linux hosts and on hosts without systemd.
# Cron-install targets are no-ops on macOS dev machines.
function _require_systemd_host() {
    if [ "$(uname)" != 'Linux' ]; then
        printf '✗ chat-cron targets are Linux-only (host is %s).%s' "$(uname)" $'\n' 1>&2
        return 1
    fi
    if ! command -v systemctl >/dev/null 2>&1; then
        printf '✗ systemctl not found on PATH — host has no systemd.%s' $'\n' 1>&2
        return 1
    fi
}

# Install chat-embed-snapshots.{service,timer} into /etc/systemd/system/.
# Templates live under provisioning/systemd/. @PROJECT_DIR@ is replaced
# with the absolute path of the repo checkout, so the unit's
# WorkingDirectory + EnvironmentFile resolve correctly on the host.
function run_chat_cron_install() {
    _require_systemd_host || return 1

    local project_dir
    project_dir="$(pwd)"

    local template_dir="${project_dir}/provisioning/systemd"
    local service_in="${template_dir}/chat-embed-snapshots.service.in"
    local timer_src="${template_dir}/chat-embed-snapshots.timer"

    if [ ! -f "${service_in}" ] || [ ! -f "${timer_src}" ]; then
        printf '✗ Missing unit templates under %s%s' "${template_dir}" $'\n' 1>&2
        return 1
    fi

    local tmp_service
    tmp_service="$(mktemp)"
    sed "s|@PROJECT_DIR@|${project_dir}|g" "${service_in}" > "${tmp_service}"

    printf '%s%s' '→ Installing chat-embed-snapshots.{service,timer} into /etc/systemd/system/ (sudo required)' $'\n'
    sudo install -m 0644 "${tmp_service}" /etc/systemd/system/chat-embed-snapshots.service
    sudo install -m 0644 "${timer_src}"   /etc/systemd/system/chat-embed-snapshots.timer
    rm -f "${tmp_service}"

    sudo systemctl daemon-reload
    sudo systemctl enable --now chat-embed-snapshots.timer

    printf '%s%s' '✓ Timer enabled. Next fire:' $'\n'
    systemctl list-timers chat-embed-snapshots.timer --no-pager || true
}

# Disable the timer and remove the units. Tolerates partial state
# (e.g. uninstall after a failed install). Does not touch journal logs.
function run_chat_cron_uninstall() {
    _require_systemd_host || return 1

    printf '%s%s' '→ Disabling chat-embed-snapshots.timer and removing units (sudo required)' $'\n'
    sudo systemctl disable --now chat-embed-snapshots.timer 2>/dev/null || true
    sudo rm -f /etc/systemd/system/chat-embed-snapshots.service \
               /etc/systemd/system/chat-embed-snapshots.timer
    sudo systemctl daemon-reload

    printf '%s%s' '✓ Removed.' $'\n'
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
