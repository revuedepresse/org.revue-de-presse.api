#!/usr/bin/env bash
set -Eeuo pipefail

function install_app_requirements() {
    local project_dir
    project_dir='/var/www/revue-de-presse.org'

    rm -rf "${project_dir}"'/vendor'

    local WORKER_UID
    local WORKER_GID
    local GITHUB_API_TOKEN

    source "${project_dir}/.env.local"

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

    # [Command line github-oauth](https://getcomposer.org/doc/articles/authentication-for-private-packages.md#command-line-github-oauth)
    composer config -g github-oauth.github.com "${GITHUB_API_TOKEN}"

    composer install \
        --prefer-dist \
        --no-dev \
        --no-interaction \
        --classmap-authoritative

    chown --verbose -R "${WORKER_UID}:${WORKER_GID}" /scripts

    chmod     o-rwx /scripts
    chmod -R ug+rx  /scripts
    chmod -R  u+w   /scripts

    if [ ! -e '/templates/www.conf.dist' ];
    then
        echo 'Missing configuration file.'

        return 1
    fi

    \cat '/templates/www.conf.dist' | \
    \sed -E 's/__SOCK__/revue-de-presse.org/g' | \
    \sed -E 's/__UID__/'"${WORKER_UID}"'/g' | \
    \sed -E 's/__GID__/'"${WORKER_GID}"'/g' \
    > "${project_dir}/provisioning/containers/service/templates/www.conf" && \
    printf '%s.%s' 'Successfully copied php-fpm template' $'\n'

    echo '--- BEGIN ---'
    \cat '/templates/www.conf'
    echo '--- END ---'

    find "${project_dir}"  \
        -maxdepth 1 \
        -executable \
        -readable \
        -type d \
        -not -path "${project_dir}"'/.git/**/*' \
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
        -not -path "${project_dir}"'/.git/**/*' \
        -exec /bin/bash -c 'export file_path="{}" && \chown '"${WORKER_UID}"':'"${WORKER_GID}"' "${file_path}"' \; \
        -exec /bin/bash -c 'export file_path="{}" && \chmod og-rwx "${file_path}"' \; \
        -exec /bin/bash -c 'export file_path="{}" && \chmod g+r "${file_path}"' \; && \
        printf '%s.%s' 'Successfully changed files permissions' $'\n'

    find "${project_dir}"  \
        -type f \
        -not -path "${project_dir}"'/bin' \
        -exec /bin/bash -c 'export file_path="{}" && \chown --recursive '"${WORKER_UID}"':'"${WORKER_GID}"' "${file_path}"' \; \
        -exec /bin/bash -c 'export file_path="{}" && \chmod --recursive ug+x "${file_path}"' \; \
        printf '%s.%s' 'Successfully changed directories permissions' $'\n'
}
install_app_requirements

set +Eeuo pipefail
