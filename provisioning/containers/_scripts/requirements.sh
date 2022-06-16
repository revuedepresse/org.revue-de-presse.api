#!/usr/bin/env bash
set -Eeuo pipefail

function add_system_user_group() {
    if [ $(cat /etc/group | grep "${WORKER_GID}" -c) -eq 0 ]; then
        groupadd \
            --gid "${WORKER_GID}" \
            worker
    fi

    useradd \
        --shell /usr/sbin/nologin \
        --uid ${WORKER_UID} \
        --gid ${WORKER_GID} \
        --non-unique \
        --no-user-group \
        --no-create-home \
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

    chown -R worker. \
        /var/www/${WORKER}/var/log/* \
        /entrypoint.sh \
        /start.sh

    chmod -R ug+x \
        /entrypoint.sh \
        /start.sh
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

function install_system_packages() {
    # Update package source repositories
    apt-get update

    # Install packages with package management system frontend (apt)
    apt-get install --assume-yes \
        --assume-yes \
        apt-utils \
        ca-certificates \
        git \
        libcurl4-gnutls-dev \
        libicu-dev \
        libjpeg-dev \
        libpng-dev \
        libpq-dev \
        librabbitmq-dev \
        libsodium-dev \
        make \
        procps \
        tini \
        unzip \
        wget \
        zlib1g-dev
}

set -Eeuo pipefail
