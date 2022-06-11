#!/usr/bin/env bash
set -Eeuo pipefail

function configure_php_fpm_pool() {
    local project_dir
    project_dir="${1}"

    if [ -z "${project_dir}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' '1st argument' 'project directory' $'\n'

        return 1

    fi

    if [ ! -e '/templates/www.conf.dist' ];
    then

        printf 'A %s is expected (%s).%s' 'regular file' 'PHP FPM configuration file template.' $'\n' 1>&2

        exit 1

    fi

    \cat '/templates/www.conf.dist' | \
    \sed -E 's/__SERVICE__/'"${SERVICE}"'/g' | \
    \sed -E 's/__UID__/'"${WORKER_UID}"'/g' | \
    \sed -E 's/__GID__/'"${WORKER_GID}"'/g' \
    > "${project_dir}/provisioning/containers/service/templates/www.conf" && \
    printf '%s.%s' 'Successfully copied php-fpm template' $'\n'

    echo '--- BEGIN ---'
    \cat '/templates/www.conf'
    echo '--- END ---'
}

function install_php_libraries() {
    # [Command line github-oauth](https://getcomposer.org/doc/articles/authentication-for-private-packages.md#command-line-github-oauth)
    composer config -g github-oauth.github.com "${GITHUB_API_TOKEN}"

    composer install \
        --prefer-dist \
        --no-dev \
        --no-interaction \
        --classmap-authoritative
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

    chown --verbose -R "${WORKER_UID}:${WORKER_GID}" /scripts

    chmod     o-rwx /scripts
    chmod -R ug+rx  /scripts
    chmod -R  u+w   /scripts

    find "${project_dir}"  \
        -maxdepth 1 \
        -executable \
        -readable \
        -type d \
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
        -exec /bin/bash -c 'export file_path="{}" && \chown '"${WORKER_UID}"':'"${WORKER_GID}"' "${file_path}"' \; \
        -exec /bin/bash -c 'export file_path="{}" && \chmod og-rwx "${file_path}"' \; \
        -exec /bin/bash -c 'export file_path="{}" && \chmod g+r "${file_path}"' \; && \
        printf '%s.%s' 'Successfully changed files permissions' $'\n'

    find "${project_dir}"  \
        -type f \
        -not -path "${project_dir}"'/bin' \
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

    if [ -z "${SERVICE}" ];
    then

      printf 'A %s is expected as %s ("%s").%s' 'non-empty string' 'environment variable' 'SERVICE' $'\n'

      return 1

    fi

    local project_dir
    project_dir='/var/www/'${SERVICE}

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
    configure_php_fpm_pool "${project_dir}"
    remove_distributed_version_control_system_files_git "${project_dir}"
    set_file_permissions "${project_dir}"
}
install_app_requirements

set +Eeuo pipefail
