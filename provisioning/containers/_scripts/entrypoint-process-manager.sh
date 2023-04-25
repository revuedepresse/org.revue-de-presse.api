#!/usr/bin/env bash
set -Eeo pipefail

source '/scripts/requirements.sh'

function run() {
    create_log_files_when_non_existing "${WORKER}"

    local cmd
    cmd="$(cat <<-CMD
    source /start.sh
CMD
)"

    echo "About to run command:"
    echo "${cmd}"

    /bin/bash -c "${cmd}" >> "/var/www/${WORKER}/var/log/${WORKER}.log" 2>> "/var/www/${WORKER}/var/log/${WORKER}.error.log"
}
run

set +Eeo pipefail
