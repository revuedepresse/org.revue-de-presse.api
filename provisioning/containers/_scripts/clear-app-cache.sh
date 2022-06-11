#!/usr/bin/env bash
set -Eeuo pipefail

function clear_cache() {
    printf 'Started clearing caches'

    php \
    ./bin/console cache:clear \
    --env=prod \
    --no-debug

    php \
    ./bin/console cache:warmup \
    --no-debug \
    --env=prod

    php \
    ./bin/console cache:clear \
    --no-debug \
    --env=dev

    php \
    ./bin/console cache:warmup \
    --no-debug \
    --env=dev

    php \
    ./bin/console \
    doctrine:cache:clear-metadata \
    --env=dev

    php \
    ./bin/console \
    doctrine:cache:clear-metadata \
    --env=prod

    printf 'Finished clearing caches'
}
clear_cache

set +Eeuo pipefail
