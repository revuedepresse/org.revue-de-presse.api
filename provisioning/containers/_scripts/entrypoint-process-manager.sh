#!/usr/bin/env bash
set -Eeo pipefail

source '/scripts/requirements.sh'

function dockerize() {
    create_log_files_when_non_existing "${WORKER}"

    local cmd
    cmd="$(cat <<-CMD
    /usr/local/bin/dockerize \
    -stdout "/var/www/${WORKER}/var/log/${WORKER}.log" \
    -stderr "/var/www/${WORKER}/var/log/${WORKER}.error.logv \
    -timeout 600s \
    /start.sh
CMD
)"

    echo "About to run command:"
    echo "${cmd}"

    /bin/bash -c "${cmd}"
}
dockerize

set +Eeo pipefail
