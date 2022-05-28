#!/usr/bin/env bash
set -Eeuo pipefail

source '/scripts/install-shared-dependencies.sh'

function install_worker_requirements() {
    install_shared_dependencies

    wget https://pecl.php.net/get/amqp-1.11.0.tgz -O /tmp/amqp-1.11.0.tgz && \
    cd /tmp && tar -xvzf /tmp/amqp-1.11.0.tgz && cd amqp-1.11.0 && \
    phpize . && ./configure --with-php-config=`which php-config` \
    && make && make install

    clean
}
install_worker_requirements

set -Eeuo pipefail

