#!/usr/bin/env bash
set -Eeuo pipefail

function install_php_libraries() {
    mkdir --parents "${COMPOSER_HOME}"

    # [Command line github-oauth](https://getcomposer.org/doc/articles/authentication-for-private-packages.md#command-line-github-oauth)
    composer config -g github-oauth.github.com "${GITHUB_API_TOKEN}"

    composer install \
        --prefer-dist \
        --no-dev \
        --no-interaction \
        --classmap-authoritative

    if [ -n "${COMPOSER_UPDATE_DEPS}" ];
    then
        composer update \
            --prefer-dist \
            --no-dev \
            --no-interaction
    fi
}

function remove_binaries_vendors() {
    if [ -z "${project_dir}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' '1st argument' 'project directory' $'\n'

        return 1

    fi

    rm -f "${project_dir}/bin/behat"
    rm -f "${project_dir}/bin/doctrine"
    rm -f "${project_dir}/bin/doctrine-dbal"
    rm -f "${project_dir}/bin/doctrine-migrations"
    rm -f "${project_dir}/bin/google-cloud-batch"
    rm -f "${project_dir}/bin/jp.php"
    rm -f "${project_dir}/bin/patch-type-declarations"
    rm -f "${project_dir}/bin/php-parse"
    rm -f "${project_dir}/bin/phpunit"
    rm -f "${project_dir}/bin/simple-phpunit"
    rm -f "${project_dir}/bin/sql-formatter"
    rm -f "${project_dir}/bin/var-dump-server"
    rm -f "${project_dir}/bin/yaml-lint"

    rm -rf "${project_dir}"'/vendor'
}

function set_file_permissions() {
    local project_dir
    project_dir="${1}"

    if [ -z "${project_dir}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' '1st argument' 'project directory' $'\n'

        return 1

    fi

    if [ ! -d "${project_dir}/.git" ];
    then
        rm --recursive --force --verbose "${project_dir}/.git"
    fi

    chown --verbose -R "${WORKER_UID}:${WORKER_GID}" /scripts

    chmod --verbose     o-rwx /scripts
    chmod --verbose -R ug+rx  /scripts
    chmod --verbose -R  u+w   /scripts

    find "${project_dir}"  \
        -maxdepth 1 \
        -executable \
        -readable \
        -type d \
        -not -path "${project_dir}"'/provisioning/volumes' \
        -exec /bin/bash -c 'export file_path="{}" && \chown --recursive '"${WORKER_UID}"':'"${WORKER_GID}"' "${file_path}"' \; \
        -exec /bin/bash -c 'export file_path="{}" && \chmod --recursive og-rwx "${file_path}"' \; \
        -exec /bin/bash -c 'export file_path="{}" && \chmod --recursive g+rx "${file_path}"' \; && \
        printf '%s.%s' 'Successfully changed directories permissions' $'\n'

    find "${project_dir}" \
        -maxdepth 2 \
        -executable \
        -readable \
        -path "${project_dir}"'/var' \
        -type d \
        -exec /bin/bash -c 'export file_path="{}" && \chmod --recursive g+w "${file_path}"' \; && \
        printf '%s.%s' 'Successfully made var directories writable' $'\n'

    find "${project_dir}" \
        -type f \
        -readable \
        -not -path "${project_dir}"'/provisioning/volumes' \
        -exec /bin/bash -c 'export file_path="{}" && \chown '"${WORKER_UID}"':'"${WORKER_GID}"' "${file_path}"' \; \
        -exec /bin/bash -c 'export file_path="{}" && \chmod og-rwx "${file_path}"' \; \
        -exec /bin/bash -c 'export file_path="{}" && \chmod g+r "${file_path}"' \; && \
        printf '%s.%s' 'Successfully changed files permissions' $'\n'

    find "${project_dir}"  \
        -type f \
        -not -path "${project_dir}"'/bin' \
        -not -path "${project_dir}"'/provisioning/volumes' \
        -exec /bin/bash -c 'export file_path="{}" && \chown --recursive '"${WORKER_UID}"':'"${WORKER_GID}"' "${file_path}"' \; \
        -exec /bin/bash -c 'export file_path="{}" && \chmod --recursive ug+x "${file_path}"' \; && \
        printf '%s.%s' 'Successfully changed binaries permissions' $'\n'
}

function remove_distributed_version_control_system_files_git() {
    if [ ! -d "${project_dir}/.git" ];
    then
        rm --recursive --force --verbose "${project_dir}/.git"
    fi
}

function install_app_requirements() {
    local APP_SECRET
    local GITHUB_API_TOKEN
    local WORKER_UID
    local WORKER_GID

    if [ -z "${WORKER}" ];
    then

      printf 'A %s is expected as %s ("%s").%s' 'non-empty string' 'environment variable' 'WORKER' $'\n'

      return 1

    fi

    local project_dir
    project_dir='/var/www/'${WORKER}

    source "${project_dir}/.env.local"

    if [ -z "${APP_SECRET}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' 'environment variable' 'APP_SECRET' $'\n'

        return 1

    fi

    if [ -z "${GITHUB_API_TOKEN}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' 'environment variable' 'GITHUB_API_TOKEN' $'\n'

        return 1

    fi

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

    remove_binaries_vendors "${project_dir}"
    install_php_libraries
    remove_distributed_version_control_system_files_git "${project_dir}"
    set_file_permissions "${project_dir}"
}
install_app_requirements

set +Eeuo pipefail
