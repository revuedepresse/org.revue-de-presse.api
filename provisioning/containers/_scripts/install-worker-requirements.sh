#!/usr/bin/env bash
set -Eeuo pipefail

function add_system_user_group() {
    if [ $(cat /etc/group | grep "${WORKER_GID}" -c) -eq 0 ]; then
        groupadd \
            --gid "${WORKER_GID}" \
            service
    fi

    if [ $(cat /etc/passwd | grep "${WORKER_UID}" -c) -eq 0 ]; then
        useradd \
            --shell /usr/sbin/nologin \
            --uid ${WORKER_UID} \
            --gid ${WORKER_GID} \
            --no-user-group \
            --no-create-home \
            service
    fi

}

function clear_package_management_system_cache() {
    # Remove packages installed with apt except for tini
    apt-get remove --assume-yes build-essential gcc build-essential wget
    apt-get autoremove --assume-yes
    apt-get purge --assume-yes
    apt-get clean
    rm -rf /var/lib/apt/lists/*
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

function install_service_requirements() {
    install_system_packages
    add_system_user_group
    install_php_extensions
    clear_package_management_system_cache

    mkdir \
        --verbose \
        --parents \
        "/var/www/${SERVICE}"
}

function install_system_packages() {
    echo 'deb [trusted=yes] https://repo.symfony.com/apt/ /' | tee /etc/apt/sources.list.d/symfony-cli.list

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
        libpq-dev \
        librabbitmq-dev \
        libsodium-dev \
        procps \
        symfony-cli \
        tini \
        unzip \
        wget
}

install_service_requirements

set -Eeuo pipefail

