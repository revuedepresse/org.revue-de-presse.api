#!/usr/bin/env bash
set -Eeuo pipefail

trap "exit 1" TERM
export install_requirements_pid=$$

function add_system_user_group() {
    if [ $(cat /etc/group | grep "${SERVICE_OWNER_GID}" -c) -eq 0 ]; then
        groupadd \
            --gid "${SERVICE_OWNER_GID}" \
            service
    fi

    if [ $(cat /etc/passwd | grep "${SERVICE_OWNER_UID}" -c) -eq 0 ]; then
        useradd \
            --shell /usr/sbin/nologin \
            --uid ${SERVICE_OWNER_UID} \
            --gid ${SERVICE_OWNER_GID} \
            --no-user-group \
            --no-create-home \
            service
    fi
}

function clear_package_management_system_cache() {
    # Remove packages installed with apt except for tini
    apt-get remove --assume-yes build-essential gcc build-essential wget
    apt-get autoremove --assume-yes &&
    apt-get purge --assume-yes
    apt-get clean &&
    rm -rf /var/lib/apt/lists/*
}

function install_system_packages() {
    echo 'deb [trusted=yes] https://repo.symfony.com/apt/ /' | tee /etc/apt/sources.list.d/symfony-cli.list
    apt-get update
    apt-get install --assume-yes \
        apt-utils \
        ca-certificates \
        git \
        libcurl4-gnutls-dev \
        libicu-dev \
        libcurl4-gnutls-dev \
        libfreetype-dev \
        libicu-dev \
        libjpeg-dev \
        libpng-dev \
        libwebp-dev \
        libpq-dev \
        libsodium-dev \
        parallel \
        procps \
        symfony-cli \
        tini \
        unzip \
        wget

    # Install blackfire profiling agent
    install_blackfire
}

function install_blackfire() {
    wget -q -O - https://packages.blackfire.io/gpg.key | \
    dd of=/usr/share/keyrings/blackfire-archive-keyring.asc
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/blackfire-archive-keyring.asc] http://packages.blackfire.io/debian any main" | \
    tee /etc/apt/sources.list.d/blackfire.list

    apt update
    apt install blackfire blackfire-php --assume-yes
}

function install_php_extensions() {
    docker-php-ext-install \
        bcmath \
        mysqli \
        intl \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        sockets \
        sodium

    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
    docker-php-ext-install gd

    docker-php-ext-enable opcache

    wget https://github.com/xdebug/xdebug/archive/3.2.0.zip \
    --output-document /tmp/3.2.0.zip
    cd /tmp || exit
    unzip /tmp/3.2.0.zip
    cd xdebug-3.2.0 || exit
    phpize .
    ./configure --with-php-config="$(which php-config)"
    make
    make install

    wget https://github.com/DataDog/dd-trace-php/releases/latest/download/datadog-setup.php \
    --output-document=/tmp/datadog-setup.php
    cd /tmp || exit
    php datadog-setup.php \
    --php-bin all \
    --enable-appsec \
    --enable-profiling
}

function install_service_requirements() {
    add_system_user_group
    install_system_packages
    install_php_extensions
    clear_package_management_system_cache

    if [ -e /start.sh ];
    then

          chown \
              --verbose \
              "${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID}" \
              /start.sh

          chmod \
              --verbose \
              ug+x \
              /start.sh

    fi

    mkdir \
        --verbose \
        --parents \
        "/var/www/${SERVICE}"
}

function install_app_requirements() {
    local project_dir
    project_dir="/var/www/${SERVICE}"

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

    local APP_SECRET
    local GITHUB_API_TOKEN
    local SERVICE
    local SERVICE_OWNER_UID
    local SERVICE_OWNER_GID

    source "${project_dir}/.env.local"

    if [ -z "${APP_SECRET}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' 'environment variable' 'APP_SECRET' $'\n'

        kill -s TERM $install_requirements_pid

    fi

    if [ -z "${GITHUB_API_TOKEN}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' 'environment variable' 'GITHUB_API_TOKEN' $'\n'

        kill -s TERM $install_requirements_pid

    fi

    if [ -z "${SERVICE}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' 'environment variable' 'SERVICE' $'\n'

        kill -s TERM $install_requirements_pid

    fi

    if [ -z "${SERVICE_OWNER_UID}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user uid' 'SERVICE_OWNER_UID' $'\n'

        kill -s TERM $install_requirements_pid

    fi

    if [ -z "${SERVICE_OWNER_GID}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user gid' 'SERVICE_OWNER_GID' $'\n'

        kill -s TERM $install_requirements_pid

    fi

    # [Command line github-oauth](https://getcomposer.org/doc/articles/authentication-for-private-packages.md#command-line-github-oauth)
    composer config -g github-oauth.github.com "${GITHUB_API_TOKEN}"

    composer install \
        --prefer-dist \
        --no-dev \
        --no-interaction \
        --classmap-authoritative

    chown --verbose -R "${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID}" /scripts

    chmod     o-rwx /scripts
    chmod -R ug+rx  /scripts
    chmod -R  u+w   /scripts

    if [ ! -e '/templates/www.conf.dist' ];
    then
        echo 'Missing configuration file.'

        return 1
    fi

    \cat '/templates/www.conf.dist' | \
    \sed -E 's/__SERVICE__/'"${SERVICE}"'/g' | \
    \sed -E 's/__UID__/'"${SERVICE_OWNER_UID}"'/g' | \
    \sed -E 's/__GID__/'"${SERVICE_OWNER_GID}"'/g' \
    > "${project_dir}/provisioning/containers/service/templates/www.conf" && \
    printf '%s.%s' 'Successfully copied php-fpm template' $'\n'

    echo '--- BEGIN ---'
    \cat '/templates/www.conf'
    echo '--- END ---'

    if [ ! -d "${project_dir}/.git" ];
    then

        rm --recursive --force --verbose "${project_dir}/.git"

    fi

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
        -exec sh -c "${change_directory_permissions}" shell {} "${SERVICE_OWNER_UID}" "${SERVICE_OWNER_GID}" \; && \
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
        -exec sh -c "${change_file_permissions}" shell {} \; && \
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
        -exec sh -c "${change_binaries_permissions}" shell {} \; && \
        printf '%s.%s' 'Successfully changed binaries permissions' $'\n'
}

set -Eeuo pipefail

