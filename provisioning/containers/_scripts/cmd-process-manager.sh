#!/usr/bin/env bash
set -Eeo pipefail

start() {
    cd "/var/www/${WORKER}/public" || exit

    export \
      APP_ENV=prod \
      MEMORY_LIMIT=256M \
      MESSAGES=100 \
      PROJECT_DIR="/var/www/${WORKER}/public" \
      PROJECT_NAME='wildcard' \
      SYMFONY_ENV=prod \
      TIME_LIMIT=600

    pm2 start bin/console make consume-fetch-publication-messages  \
      2>> "/var/www/${WORKER}/var/log/${WORKER}.error.log" | \
      tee --append "/var/www/${WORKER}/var/log/${WORKER}.log"
}
start

set +Eeo pipefail
