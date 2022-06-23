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
        APP_ENV=prod \
        MEMORY_LIMIT=256M \
        MESSAGES=100 \
        PROJECT_DIR="${project_dir}" \
        PROJECT_NAME='revue-de-presse.org' \
        SYMFONY_ENV=prod \
        TIME_LIMIT=600

    php bin/console cache:clear --verbose

    local asdf_dir
    asdf_dir="${project_dir}/var/home/asdf"

    install_process_manager "${asdf_dir}"

    ./node_modules/.bin/pm2 \
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
