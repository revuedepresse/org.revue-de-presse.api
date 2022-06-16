#!/usr/bin/env bash
set -Eeo pipefail

source '/scripts/requirements.sh'

function install_process_manager() {
    local project_dir
    project_dir="/var/www/${WORKER}"

    if [ -z "${WORKER_UID}" ];
    then

      printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user uid' 'WORKER_UID' $'\n'

      return 1

    fi

    if [ -z "${WORKER_GID}" ];
    then

      printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user gid' 'WORKER_GID' $'\n'

      return 1

    fi

    cd "${project_dir}" || exit

    npm install pm2 -g
    pm2 install pm2-logrotate
}

function install_process_manager_requirements() {
    install_system_packages
    add_system_user_group
    create_log_files_when_non_existing "${WORKER}"
    install_dockerize
    install_process_manager
    clear_package_management_system_cache
}
install_process_manager_requirements

set +Eeo pipefail
