#!/usr/bin/env bash
set -Eeuo pipefail

function install_pipeline() {
    # [PHP](https://documentation.codeship.com/basic/languages-frameworks/php/)
    phpenv local 8.0
    phpenv global 8.0
    phpenv rehash

    wget https://pecl.php.net/get/amqp-1.11.0.tgz -O /tmp/amqp-1.11.0.tgz
    cd /tmp && tar -xvzf /tmp/amqp-1.11.0.tgz && cd amqp-1.11.0
    "${HOME}/.phpenv/versions/$(phpenv version-name)/bin/phpize" .
    ./configure --with-php-config="${HOME}/.phpenv/versions/$(phpenv version-name)/bin/php-config"
    make && make install

    echo "extension=${HOME}/.phpenv/versions/8.0.18/lib/php/extensions/no-debug-non-zts-20200930/amqp.so" \
    > "${HOME}/.phpenv/versions/$(phpenv version-name)/etc/conf.d/amqp.ini"

    (
        # [libsodium](https://docs.cloudbees.com/docs/cloudbees-codeship/latest/basic-languages-frameworks/php#_libsodium)
        LIBSODIUM_VERSION='1.0.18'
        LIBSODIUM_DIR="${HOME}/cache/libsodium"
        CACHED_DOWNLOAD="${HOME}/cache/libsodium-${LIBSODIUM_VERSION}.tar.gz"

        mkdir -p "${HOME}/libsodium"
        wget --continue --output-document "${CACHED_DOWNLOAD}" "https://download.libsodium.org/libsodium/releases/libsodium-${LIBSODIUM_VERSION}.tar.gz"
        tar -xaf "${CACHED_DOWNLOAD}" --strip-components=1 --directory "${HOME}/libsodium"

        cd "${HOME}/libsodium" || exit
        ./configure --prefix="${LIBSODIUM_DIR}" && make && make install

        if [ $? -eq 0 ];
        then
            printf '%s.%s' 'Installed libsodium successfully' $'\n'
        else
            printf '%s.%s' 'Could not install libsodium.' $'\n'

            return 1
        fi
    )

    pecl channel-update pecl.php.net

    LD_LIBRARY_PATH="${HOME}/cache/libsodium/lib PKG_CONFIG_PATH=${HOME}/cache/libsodium/lib/pkgconfig" \
    LDFLAGS="-L${HOME}/cache/libsodium/lib" pecl install -f libsodium
    echo "extension=${HOME}/.phpenv/versions/8.0.18/lib/php/extensions/no-debug-non-zts-20200930/libsodium.so" \
    > "${HOME}/.phpenv/versions/$(phpenv version-name)/etc/conf.d/libsodium.ini"

    local xdebug_configuration_file
    xdebug_configuration_file="${HOME}/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini"
    test -e "${xdebug_configuration_file}" && rm -f "${xdebug_configuration_file}"

    cd ~/src/github.com/thierrymarianne/api.revue-de-presse.org || exit
    cp provisioning/continuous-integration/parameters_test_codeship.yml.dist .env.test

    COMPOSER_HOME=${HOME}/cache/composer composer config -g github-oauth.github.com "${GITHUB_ACCESS_TOKEN}"
    COMPOSER_HOME=${HOME}/cache/composer composer install --prefer-dist --no-scripts

    php bin/console doctrine:schema:create -n -e test
}

LANG="${LANG}" \
RAILSONFIRE="${RAILSONFIRE}" \
CODESHIP="${CODESHIP}" \
DISPLAY="${DISPLAY}" \
GOPATH="${GOPATH}" \
PATH="${PATH}" \
PARALLEL_TEST_PROCESSORS="${PARALLEL_TEST_PROCESSORS}" \
PG_USER="${PG_USER}" \
PGUSER="${PGUSER}" \
PG_PASSWORD="${PG_PASSWORD}" \
PGPASSWORD="${PGPASSWORD}" \
PIP_CACHE_DIR="${PIP_CACHE_DIR}" \
MYSQL_USER="${MYSQL_USER}" \
MYSQL_PASSWORD="${MYSQL_PASSWORD}" \
COMPOSER_HOME="${COMPOSER_HOME}" \
CODESHIP_VIRTUALENV="${CODESHIP_VIRTUALENV}" \
COMMIT_ID="${COMMIT_ID}" \
CI="${CI}" \
CI_BUILD_NUMBER="${CI_BUILD_NUMBER}" \
CI_BUILD_ID="${CI_BUILD_ID}" \
CI_BUILD_URL="${CI_BUILD_URL}" \
CI_PULL_REQUEST="${CI_PULL_REQUEST}" \
CI_PR_NUMBER="${CI_PR_NUMBER}" \
CI_BRANCH="${CI_BRANCH}" \
CI_COMMIT_ID="${CI_COMMIT_ID}" \
CI_COMMIT_MESSAGE="${CI_COMMIT_MESSAGE}" \
CI_COMMITTER_NAME="${CI_COMMITTER_NAME}" \
CI_COMMITTER_EMAIL="${CI_COMMITTER_EMAIL}" \
CI_COMMITTER_USERNAME="${CI_COMMITTER_USERNAME}" \
CI_MESSAGE="${CI_MESSAGE}" \
CI_NAME="${CI_NAME}" \
CI_NODE_TOTAL="${CI_NODE_TOTAL}" \
CI_REPO_NAME="${CI_REPO_NAME}" \
GITHUB_ACCESS_TOKEN="${GITHUB_ACCESS_TOKEN}" \
SYMFONY_DEPRECATIONS_HELPER="${SYMFONY_DEPRECATIONS_HELPER}" \
install_pipeline

set +Eeuo pipefail
