#!/usr/bin/env bash
set -Eeo pipefail

start() {
    local project_dir
    project_dir="/var/www/${WORKER}"

    cd "${project_dir}" || exit

    export \
      APP_ENV=prod \
      MEMORY_LIMIT=256M \
      MESSAGES=100 \
      PROJECT_DIR="${project_dir}" \
      PROJECT_NAME='wildcard' \
      SYMFONY_ENV=prod \
      TIME_LIMIT=600

    pm2 \
      --instances 4 \
      --log-type json \
      --max-memory-restart 268435456 \
      --no-daemon \
      --restart-delay=10000 \
      start bin/consume-fetch-publication-messages.sh \
      --name 'Dispatch Fetch member publications AMQP messages' \
      2>> "${project_dir}/var/log/${WORKER}.error.log" | \
      tee --append "${project_dir}/var/log/${WORKER}.log"
}
start

set +Eeo pipefail
