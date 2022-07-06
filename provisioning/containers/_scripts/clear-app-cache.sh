#!/usr/bin/env bash
set -Eeuo pipefail

function clear_cache() {
    printf '%s.%s' 'Started clearing caches' $'\n'

    local environments
    environments=( dev prod )

    for env in "${environments[@]}"; do
        php \
        ./bin/console cache:clear \
        --no-debug \
        --env=${env}

        php \
        ./bin/console cache:warmup \
        --no-debug \
        --env=${env}

        php \
        ./bin/console \
        doctrine:cache:clear-metadata \
        --no-debug \
        --env=${env}

        php \
        ./bin/console doctrine:mapping:info \
        --no-debug \
        --env=${env}

    done

    printf '%s.%s' 'Finished clearing caches' $'\n'
}
clear_cache

set +Eeuo pipefail
