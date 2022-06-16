#!/usr/bin/env bash
set -Eeuo pipefail

source '/scripts/requirements.sh'

function install_worker_requirements() {
    install_system_packages
    add_system_user_group
    install_php_extensions
    clear_package_management_system_cache

    mkdir \
        --verbose \
        --parents \
        "/var/www/${WORKER}"
}
install_worker_requirements

set -Eeuo pipefail

