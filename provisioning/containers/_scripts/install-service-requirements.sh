#!/usr/bin/env bash
set -Eeuo pipefail

source '/scripts/install-shared-dependencies.sh'

function install_service_requirements() {
    install_shared_dependencies
    clean
}
install_service_requirements

set -Eeuo pipefail

