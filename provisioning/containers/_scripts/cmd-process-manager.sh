#!/usr/bin/env bash
set -Eeo pipefail

source '/scripts/requirements.sh'

start() {
    local project_dir
    project_dir="/var/www/${WORKER}"

    cd "${project_dir}" || exit

    if [ ! -d "${project_dir}/.git" ];
    then
        rm --recursive --force --verbose "${project_dir}/.git"
    fi

    export \
        MESSAGES=50 \
        PROJECT_DIR="${project_dir}" \
        PROJECT_NAME='wildcard' \
        TIME_LIMIT=300

    configure_blackfire_client
    php bin/console cache:clear --verbose

    if [ ! -e ./.pm2-installed ];
    then
        local asdf_dir
        asdf_dir="${project_dir}/var/home/asdf"

        install_process_manager "${asdf_dir}"
    fi

    local total_workers
    if [ -z "${TOTAL_WORKERS}" ];
    then
        total_workers=16
    else
        total_workers=${TOTAL_WORKERS}
    fi

    local memory_limit
    if [ -z "${MEMORY_LIMIT}" ];
    then
        memory_limit='256M'
    else
        memory_limit=${MEMORY_LIMIT}
    fi

    export MEMORY_LIMIT="${memory_limit}"

    ./node_modules/.bin/pm2 \
        --instances "${total_workers}" \
        --log "./var/log/${APP_ENV}.json" \
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
