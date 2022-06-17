#!/usr/bin/env bash
set -Eeuo pipefail

source '/scripts/requirements.sh'

function install_worker_requirements() {
    clear_package_management_system_cache

    mkdir \
        --verbose \
        --parents \
        "/var/www/${WORKER}"
}
install_worker_requirements

set -Eeuo pipefail

