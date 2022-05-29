#!/usr/bin/env bash
set -Eeuo pipefail

function clean() {
    # Remove packages installed with apt except for tini
    apt-get remove --assume-yes build-essential gcc build-essential wget
    apt-get autoremove --assume-yes &&
    apt-get purge --assume-yes
    apt-get clean &&
    rm -rf /var/lib/apt/lists/*
}

function install_shared_dependencies() {
    echo 'deb [trusted=yes] https://repo.symfony.com/apt/ /' | tee /etc/apt/sources.list.d/symfony-cli.list

    # Update package source repositories
    apt-get update

    mkdir \
        --verbose \
        --parents \
        /var/www/revue-de-presse.org

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

    # Install debian packages
    apt-get install \
        --assume-yes \
        apt-utils \
        ca-certificates \
        git \
        libcurl4-gnutls-dev \
        libicu-dev \
        librabbitmq-dev \
        libsodium-dev \
        procps \
        symfony-cli \
        tini \
        unzip \
        wget

    docker-php-ext-install \
        bcmath \
        mysqli \
        intl \
        pcntl \
        pdo_mysql \
        sockets

    cd /tmp || exit
    wget https://github.com/xdebug/xdebug/archive/3.1.4.zip
    unzip 3.1.4.zip
    cd xdebug-3.1.4
    phpize .
    ./configure --with-php-config="$(which php-config)"
    make
    make install

    cd /tmp || exit
    wget https://pecl.php.net/get/libsodium-2.0.23.tgz
    tar -xvzf /tmp/libsodium-2.0.23.tgz
    cd libsodium-2.0.23
    phpize .
    ./configure --with-php-config="$(which php-config)"
    make
    make install

    pecl install apcu-5.1.19
    docker-php-ext-enable apcu

    cd /tmp || exit
    wget https://github.com/DataDog/dd-trace-php/archive/0.74.0.tar.gz -O /tmp/datadog-php-tracer.tar.gz
    tar -xvzf /tmp/datadog-php-tracer.tar.gz

    cd dd-trace-php-0.74.0 || exit
    phpize .
    ./configure --with-php-config="$(which php-config)"
    make
    make install
}

set -Eeuo pipefail

