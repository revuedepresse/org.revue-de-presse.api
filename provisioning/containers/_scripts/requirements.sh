#!/usr/bin/env bash
set -Eeuo pipefail

function add_system_user_group() {
    if [ $(cat /etc/group | grep "${WORKER_OWNER_GID}" -c) -eq 0 ]; then
        groupadd \
            --gid "${WORKER_OWNER_GID}" \
            worker
    fi

    useradd \
        --gid ${WORKER_OWNER_GID} \
        --home-dir=/var/www \
        --no-create-home \
        --no-user-group \
        --non-unique \
        --shell /usr/sbin/nologin \
        --uid ${WORKER_OWNER_UID} \
        worker
}

function clear_package_management_system_cache() {
    # Remove packages installed with apt except for tini
    apt-get remove --assume-yes build-essential gcc build-essential wget
    apt-get autoremove --assume-yes
    apt-get purge --assume-yes
    apt-get clean
    rm -rf /var/lib/apt/lists/*
}

function configure_blackfire_client() {
    \cat '/scripts/configuration-files/blackfire.ini.dist' \
    | sed -E 's#__CLIENT_ID__#'"${BLACKFIRE_CLIENT_ID}"'#g' \
    | sed -E 's#__CLIENT_TOKEN__#'"${BLACKFIRE_CLIENT_TOKEN}"'#g' \
    > "${HOME}/.blackfire.ini"

    chown "$WORKER_OWNER_UID.${WORKER_OWNER_GID}" "${HOME}/.blackfire.ini"
}

function create_log_files_when_non_existing() {
    prefix="${1}"
    local prefix="${1}"

    if [ -z "${prefix}" ];
    then
        printf 'A %s is expected (%s).%s' 'non empty string' 'log file' $'\n'

        return 1
    fi

    mkdir \
      --verbose \
      --parents \
      "/var/www/${WORKER}/var/log"

    if [ ! -e "/var/www/${WORKER}/var/log/${prefix}.log" ];
    then

        touch "/var/www/${WORKER}/var/log/${prefix}.log"

        printf '%s "%s".%s' 'Created file located at' "/var/www/${WORKER}/var/log/${prefix}.log" $'\n'

    fi

    if [ ! -e "/var/www/${WORKER}/var/log/${prefix}.error.log" ];
    then

        touch "/var/www/${WORKER}/var/log/${prefix}.error.log"

        printf '%s "%s".%s' 'Created file located at' "/var/www/${WORKER}/var/log/${prefix}.error.log" $'\n'

    fi
}

function install_blackfire() {
    wget -q -O - https://packages.blackfire.io/gpg.key | \
    dd of=/usr/share/keyrings/blackfire-archive-keyring.asc
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/blackfire-archive-keyring.asc] http://packages.blackfire.io/debian any main" | \
    tee /etc/apt/sources.list.d/blackfire.list

    apt update
    apt install blackfire blackfire-php --assume-yes
}

function install_dockerize() {
    local dockerize_version
    dockerize_version='v0.6.1'

    # [dockerize's git repository](https://github.com/jwilder/dockerize)
    local releases_url
    releases_url="https://github.com/jwilder/dockerize/releases"

    local archive
    archive="dockerize-linux-amd64-${dockerize_version}.tar.gz"

    wget "${releases_url}/download/${dockerize_version}/${archive}" -O "${archive}"

    tar -C /usr/local/bin -xzv --file "${archive}"

    rm "${archive}"
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

    docker-php-ext-enable opcache

    wget https://github.com/xdebug/xdebug/archive/3.1.4.zip \
    --output-document /tmp/3.1.4.zip
    cd /tmp || exit
    unzip /tmp/3.1.4.zip
    cd xdebug-3.1.4 || exit
    phpize .
    ./configure --with-php-config="$(which php-config)"
    make
    make install

    wget https://github.com/DataDog/dd-trace-php/archive/0.74.0.tar.gz \
    --output-document=/tmp/datadog-php-tracer.tar.gz
    cd /tmp || exit
    tar -xvzf /tmp/datadog-php-tracer.tar.gz
    cd dd-trace-php-0.74.0 || exit
    phpize .
    ./configure --with-php-config="$(which php-config)"
    make
    make install

    wget https://github.com/DataDog/dd-trace-php/releases/latest/download/datadog-setup.php \
    --tries=40 \
    --output-document=/tmp/datadog-setup.php

    cd /tmp || exit
    php datadog-setup.php \
    --php-bin all \
    --enable-appsec \
    --enable-profiling

    wget https://pecl.php.net/get/amqp-1.11.0.tgz \
    --output-document /tmp/amqp-1.11.0.tgz && \
    cd /tmp && tar -xvzf /tmp/amqp-1.11.0.tgz && cd amqp-1.11.0 && \
    phpize .
    ./configure --with-php-config="$(which php-config)"
    make
    make install
}

function install_process_manager() {
    local asdf_dir
    asdf_dir="${1}"

    if [ -z "${asdf_dir}" ];
    then

        printf 'A %s is expected as %s (%s).%s' 'non-empty string' '1st argument' 'extendable version manager (asdf dir)' $'\n'

        return 1

    else

        rm -rf "${asdf_dir}"

    fi

    export ASDF_DIR="${asdf_dir}"

    git config --global advice.detachedHead false
    git clone https://github.com/asdf-vm/asdf.git --branch v0.10.0 "${asdf_dir}"

    echo 'export ASDF_DIR='"${asdf_dir}"        >> "${HOME}/.bashrc"
    echo '. ${ASDF_DIR}/asdf.sh'                >> "${HOME}/.bashrc"
    echo '. ${ASDF_DIR}/completions/asdf.bash'  >> "${HOME}/.bashrc"
    echo 'nodejs 16.15.1'                       >> "${HOME}/.tool-versions"

    source "${HOME}/.bashrc"

    if [ $(asdf plugin list | grep -c 'nodejs') -eq 0 ];
    then

        asdf plugin add nodejs https://github.com/asdf-vm/asdf-nodejs.git

    else

        printf '`%s` plugin for asdf has been installed already.%s' 'nodejs' '%s' 1>&2

    fi

    asdf install nodejs 16.15.1
    asdf global nodejs 16.15.1

    # [npm Config Setting](https://docs.npmjs.com/cli/v8/using-npm/config#cache)
    npm config set cache "${asdf_dir}/../npm" --global
    npm install pm2
    ./node_modules/.bin/pm2 install pm2-logrotate

    echo '' > ./.pm2-installed
}

function upgrade_packages_source() {
    echo 'Acquire::Retries "3";' > /etc/apt/apt.conf.d/80-retries

}

function install_process_manager_packages() {
    upgrade_packages_source

    # Install packages with package management system frontend (apt)
    apt-get install --assume-yes \
        curl \
        gawk \
        git \
        gpg \
        dirmngr
}

function install_process_manager_requirements() {
    install_dockerize
    install_process_manager_packages
    clear_package_management_system_cache
}

function install_shared_requirements() {
    add_system_user_group
    install_shared_system_packages
    install_worker_system_packages
    install_php_extensions
    create_log_files_when_non_existing "${WORKER}"
    set_permissions
}

function install_shared_system_packages() {
    upgrade_packages_source

    # Install packages with package management system frontend (apt)
    apt-get install --assume-yes \
        apt-utils \
        ca-certificates \
        git \
        make \
        procps \
        tini \
        unzip \
        wget

    # Install blackfire profiling agent
    install_blackfire
}

function install_worker_system_packages() {
    upgrade_packages_source

    apt-get install --assume-yes \
        libcurl4-gnutls-dev \
        libicu-dev \
        libjpeg-dev \
        libpng-dev \
        libpq-dev \
        librabbitmq-dev \
        libsodium-dev
}

function set_permissions() {
    chown worker. \
        /var/www \
        /start.sh

    chown -R worker. "/var/www/${WORKER}"/var/log/*

    chmod -R ug+x \
        /start.sh

    if [ -e /entrypoint.sh ]; then

        chown worker. /entrypoint.sh
        chmod -R ug+x /entrypoint.sh

    fi
}

set -Eeuo pipefail

