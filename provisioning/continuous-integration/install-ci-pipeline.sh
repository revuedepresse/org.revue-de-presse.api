#!/usr/bin/env bash
set -Eeuo pipefail

function install_pipeline() {
    # [PHP](https://documentation.codeship.com/basic/languages-frameworks/php/)
    phpenv local 7.4
    phpenv global 7.4
    phpenv rehash

    wget https://pecl.php.net/get/amqp-1.10.2.tgz -O /tmp/amqp-1.10.2.tgz
    cd /tmp && tar -xvzf /tmp/amqp-1.10.2.tgz && cd amqp-1.10.2
    /bin/bash -c "${HOME}/.phpenv/versions/$(phpenv version-name)/bin/phpize ."
    /bin/bash -c "./configure --with-php-config=${HOME}/.phpenv/versions/$(phpenv version-name)/bin/php-config"
    make && make install

    # [libsodium](https://docs.cloudbees.com/docs/cloudbees-codeship/latest/basic-languages-frameworks/php#_libsodium)

    LIBSODIUM_VERSION='2.0.22' \curl -sSL https://raw.githubusercontent.com/codeship/scripts/master/packages/libsodium.sh | bash -s
    LD_LIBRARY_PATH="${HOME}/cache/libsodium/lib PKG_CONFIG_PATH=${HOME}/cache/libsodium/lib/pkgconfig" \
    LDFLAGS="-L${HOME}/cache/libsodium/lib" pecl install libsodium

    /bin/bash -c "rm -f ${HOME}/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini"
    echo 'extension=amqp' > "${HOME}/.phpenv/versions/$(phpenv version-name)/etc/conf.d/amqp.ini"

    cd ~/src/github.com/thierrymarianne/api.revue-de-presse.org || exit
    cp provisioning/continuous-integration/parameters_test_codeship.yml.dist .env.test

    COMPOSER_HOME=${HOME}/cache/composer composer config -g github-oauth.github.com "${GITHUB_ACCESS_TOKEN}"
    COMPOSER_HOME=${HOME}/cache/composer composer install --prefer-dist --no-scripts

    php bin/console doctrine:schema:create -n -e test
}
install_pipeline

set +Eeuo pipefail
