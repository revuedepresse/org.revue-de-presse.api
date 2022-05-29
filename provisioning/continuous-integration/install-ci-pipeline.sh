#!/usr/bin/env bash
set -Eeuo pipefail

function install_pipeline() {
    # [PHP](https://documentation.codeship.com/basic/languages-frameworks/php/)
    phpenv local 8.0
    phpenv global 8.0
    phpenv rehash

    wget https://pecl.php.net/get/amqp-1.11.0.tgz -O /tmp/amqp-1.11.0.tgz
    cd /tmp && tar -xvzf /tmp/amqp-1.11.0.tgz && cd amqp-1.11.0
    /bin/bash -c "${HOME}/.phpenv/versions/$(phpenv version-name)/bin/phpize ."
    /bin/bash -c "./configure --with-php-config=${HOME}/.phpenv/versions/$(phpenv version-name)/bin/php-config"
    make && make install
    echo 'extension=amqp' > "${HOME}/.phpenv/versions/$(phpenv version-name)/etc/conf.d/amqp.ini"

    # [libsodium](https://docs.cloudbees.com/docs/cloudbees-codeship/latest/basic-languages-frameworks/php#_libsodium)
    wget https://raw.githubusercontent.com/codeship/scripts/master/packages/libsodium.sh -O /tmp/libsodium.sh
    LIBSODIUM_VERSION='1.0.18' bash -c '. /tmp/libsodium.sh'
    LD_LIBRARY_PATH="${HOME}/cache/libsodium/lib PKG_CONFIG_PATH=${HOME}/cache/libsodium/lib/pkgconfig" \
    LDFLAGS="-L${HOME}/cache/libsodium/lib" pecl install -f libsodium
    echo 'extension=libsodium' > "${HOME}/.phpenv/versions/$(phpenv version-name)/etc/conf.d/libsodium.ini"

    local xdebug_configuration_file
    xdebug_configuration_file="${HOME}/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini"
    /bin/bash -c "test -e ${xdebug_configuration_file} && rm -f ${xdebug_configuration_file}"

    cd ~/src/github.com/thierrymarianne/api.revue-de-presse.org || exit
    cp provisioning/continuous-integration/parameters_test_codeship.yml.dist .env.test

    COMPOSER_HOME=${HOME}/cache/composer composer config -g github-oauth.github.com "${GITHUB_ACCESS_TOKEN}"
    COMPOSER_HOME=${HOME}/cache/composer composer install --prefer-dist --no-scripts

    php bin/console doctrine:schema:create -n -e test
}
install_pipeline

set +Eeuo pipefail
