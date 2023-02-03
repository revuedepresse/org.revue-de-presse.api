#!/usr/bin/env bash
set -Eeuo pipefail

source '/scripts/requirements.sh'

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

    chown --verbose -R "${WORKER_OWNER_UID}:${WORKER_OWNER_GID}" /scripts

    chmod --verbose     o-rwx /scripts
    chmod --verbose -R ug+rx  /scripts
    chmod --verbose -R  u+w   /scripts

    local change_directory_permissions
    change_directory_permissions=<<"EOF"
        \chown --recursive $2:$3 "$1" && \
        \chmod --recursive og-rwx "$1" && \
        \chmod --recursive g+rx "$1"
EOF

    find "${project_dir}"  \
        -maxdepth 1 \
        -executable \
        -readable \
        -type d \
        -not -path "${project_dir}"'/provisioning/volumes' \
        -not -path "${project_dir}"'/public/emoji-data' \
        -exec sh -c "${change_directory_permissions}" shell {} "${WORKER_OWNER_UID}" "${WORKER_OWNER_GID}" \; && \
        printf '%s.%s' 'Successfully changed directories permissions' $'\n'

    find "${project_dir}" \
        -maxdepth 2 \
        -type d \
        -executable \
        -readable \
        -regex '.+/var.+' \
        -regex '.+/src/Media/Resources/.+.b64' \
        -not -path "${project_dir}"'/var/log' \
        -exec sh -c '\chmod --recursive ug+w "${1}"' shell {} \; && \
        printf '%s.%s' 'Successfully made var directories writable' $'\n'

    local change_file_permissions
    change_file_permissions=<<"EOF"
        \chown $2:$3 "$1" && \
        \chmod og-rwx "$1" && \
        \chmod  g+r "$1"
EOF

    find "${project_dir}" \
        -type f \
        -readable \
        -not -path "${project_dir}"'/provisioning/volumes' \
        -not -path "${project_dir}"'/public/emoji-data' \
        -exec sh -c "${change_file_permissions}" shell {} "${WORKER_OWNER_UID}" "${WORKER_OWNER_GID}" \; && \
        printf '%s.%s' 'Successfully changed files permissions' $'\n'

    local change_binaries_permissions
    change_binaries_permissions=<<"EOF"
        \chown --recursive $2:$3 "$1" && \
        \chmod --recursive ug+x "$1"
EOF

    find "${project_dir}"  \
        -type f \
        -not -path "${project_dir}"'/bin' \
        -not -path "${project_dir}"'/provisioning/volumes' \
        -not -path "${project_dir}"'/public/emoji-data' \
        -exec sh -c "${change_binaries_permissions}" shell {} "${WORKER_OWNER_UID}" "${WORKER_OWNER_GID}" \; && \
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
    local WORKER_OWNER_UID
    local WORKER_OWNER_GID

    if [ -z "${WORKER}" ];
    then

      printf 'A %s is expected as %s ("%s").%s' 'non-empty string' 'environment variable' 'WORKER' $'\n'

      return 1

    fi

    local project_dir
    project_dir='/var/www/'${WORKER}

    if [ -n "${APP_ENV}" ] && [ "${APP_ENV}" = 'test' ];
    then

        source "${project_dir}/.env.test"

    else

    source "${project_dir}/.env.local"

    fi

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

    if [ -z "${WORKER_OWNER_UID}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user uid' 'WORKER_OWNER_UID' $'\n'

        return 1

    fi

    if [ -z "${WORKER_OWNER_GID}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user gid' 'WORKER_OWNER_GID' $'\n'

        return 1

    fi

    remove_binaries_vendors "${project_dir}"
    configure_blackfire_client
    install_php_libraries
    remove_distributed_version_control_system_files_git "${project_dir}"
    set_file_permissions "${project_dir}"
}
install_app_requirements

set +Eeuo pipefail
