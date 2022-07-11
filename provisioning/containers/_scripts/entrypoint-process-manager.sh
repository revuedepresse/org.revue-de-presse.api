#!/usr/bin/env bash
set -Eeo pipefail

source '/scripts/requirements.sh'

function dockerize() {
    create_log_files_when_non_existing "${WORKER_DIR}"

    local cmd
    cmd="$(cat <<-CMD
    /usr/local/bin/dockerize \
    -stdout "/var/www/${WORKER_DIR}/var/log/${WORKER_DIR}.log" \
    -stderr "/var/www/${WORKER_DIR}/var/log/${WORKER_DIR}.error.log" \
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
