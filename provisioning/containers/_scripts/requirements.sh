#!/usr/bin/env bash
set -Eeuo pipefail

trap "exit 1" TERM
export install_requirements_pid=$$

function add_system_user_group() {
    if [ $(cat /etc/group | grep "${PROJECT_OWNER_GID}" -c) -eq 0 ]; then
        groupadd \
            --gid "${PROJECT_OWNER_GID}" \
            service
    fi

    if [ $(cat /etc/passwd | grep "${PROJECT_OWNER_UID}" -c) -eq 0 ]; then
        useradd \
            --shell /usr/sbin/nologin \
            --uid ${PROJECT_OWNER_UID} \
            --gid ${PROJECT_OWNER_GID} \
            --no-user-group \
            --no-create-home \
            service
    fi
}

function clear_package_management_system_cache() {
    # Remove packages installed with apt except for tini
    apt-get remove --assume-yes build-essential gcc wget
    apt-get autoremove --assume-yes &&
    apt-get purge --assume-yes
    apt-get clean &&
    rm -rf /var/lib/apt/lists/*
}

function install_system_packages() {
    # Output is captured to a log file so the (extremely chatty) apt output
    # doesn't dominate the build log on the happy path, but is dumped to
    # stderr if anything fails — silent apt failures used to cascade into
    # docker-php-ext-install errors that nobody could diagnose.
    local logfile=/tmp/install_system_packages.log

    if ! (
      apt-get update --quiet
      apt-get install \
          --assume-yes \
          --quiet \
          apt-utils \
          build-essential \
          ca-certificates \
          gcc \
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
          tini \
          unzip \
          wget
    ) > "${logfile}" 2>&1; then
        printf '%s%s' '❌ Failed to install required system packages — apt log:' $'\n' 1>&2
        cat "${logfile}" 1>&2
        kill -s TERM "${install_requirements_pid}"
        return 1
    fi
    printf '%s%s' '✅ Installed system packages successfully.' $'\n' 1>&2
}

function install_php_extensions() {
    # ext-sodium is REQUIRED by App\Twitter\Infrastructure\Security\Authentication\CachedApiKeyUserProvider
    # (it encrypts every cached Member payload with sodium_crypto_secretbox).
    # Removing it from this list will make the service fail at boot.
    # Same logfile-capture-and-dump-on-failure pattern as
    # install_system_packages. The previous `>> /dev/null 2>&1` made
    # production extension-install failures impossible to diagnose.
    local php_ext_logfile=/tmp/install_php_extensions.log

    # NOTE: do NOT call `docker-php-ext-enable opcache` — since PHP 8.x
    # (and confirmed on php:8.5-fpm-bookworm) Zend OPcache is STATICALLY
    # compiled into PHP itself; there is no `opcache.so` to enable, and
    # the call fails with "'opcache' does not exist", aborting the
    # whole step. OPcache shows up under `[Zend Modules]` in `php -m`
    # automatically.
    if ! (
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
    ) > "${php_ext_logfile}" 2>&1; then
        printf '%s%s' '❌ Failed to install PHP extensions — docker-php-ext-install log:' $'\n' 1>&2
        cat "${php_ext_logfile}" 1>&2
        kill -s TERM "${install_requirements_pid}"
        return 1
    fi
    printf '%s%s' '✅ Installed PHP extensions successfully.' $'\n' 1>&2

    # Fail the image build if any required extension is missing at runtime.
    # Catches regressions where the install above silently failed
    # (it's piped to /dev/null), or where a future base image drops something.
    local required_extensions=(sodium pdo_pgsql intl sockets pcntl)
    local missing=()
    for ext in "${required_extensions[@]}"; do
        if ! php -m 2>/dev/null | grep -qiE "^${ext}$"; then
            missing+=("${ext}")
        fi
    done
    if [ "${#missing[@]}" -gt 0 ]; then
        printf '%s%s' "❌ Required PHP extensions not loaded: ${missing[*]}" $'\n' 1>&2
        kill -s TERM "${install_requirements_pid}"
        return 1
    fi
    printf '%s%s' '✅ All required PHP extensions verified at runtime.' $'\n' 1>&2

    # xdebug — best-effort by design (the binary is built so dev images
    # can opt in via php.ini; prod images leave it loaded-but-disabled).
    # Still capture the log and dump on failure so a real regression
    # (e.g. PHP ABI bump that xdebug 3.5.x doesn't yet support) is
    # visible instead of a one-line warning.
    local xdebug_logfile=/tmp/install_xdebug.log

    if (
      # 3.5.x is the first xdebug line with PHP 8.5 support (3.4.7 was the
      # last 3.4.x; building it against PHP 8.5 fails at the ZEND_MODULE_API
      # check). Bump to a 3.5.x release when raising the base PHP version.
      version=3.5.1
      wget https://github.com/xdebug/xdebug/archive/${version}.zip \
      --output-document /tmp/${version}.zip
      cd /tmp || exit
      unzip /tmp/${version}.zip
      cd xdebug-${version} || exit
      phpize .
      ./configure --with-php-config="$(which php-config)"
      make
      make install
    ) > "${xdebug_logfile}" 2>&1;
    then
        printf '%s%s' '✅ Installed XDebug extension successfully.' $'\n' 1>&2
    else
        printf '%s%s' '⚠️ Could not install XDebug extension — xdebug build log (non-fatal, prod images do not load xdebug by default):' $'\n' 1>&2
        cat "${xdebug_logfile}" 1>&2
    fi
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
              "${PROJECT_OWNER_UID}:${PROJECT_OWNER_GID}" \
              /start.sh

          chmod \
              --verbose \
              ug+x \
              /start.sh

    fi

    mkdir \
        --verbose \
        --parents \
        "/var/www/${PROJECT}"
}

function install_app_requirements() {
    local project_dir
    project_dir="/var/www/${PROJECT}"

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
    local PROJECT
    local PROJECT_OWNER_UID
    local PROJECT_OWNER_GID

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

    if [ -z "${PROJECT}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty string' 'environment variable' 'PROJECT' $'\n'

        kill -s TERM $install_requirements_pid

    fi

    if [ -z "${PROJECT_OWNER_UID}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user uid' 'PROJECT_OWNER_UID' $'\n'

        kill -s TERM $install_requirements_pid

    fi

    if [ -z "${PROJECT_OWNER_GID}" ];
    then

        printf 'A %s is expected as %s ("%s").%s' 'non-empty numeric' 'system user gid' 'PROJECT_OWNER_GID' $'\n'

        kill -s TERM $install_requirements_pid

    fi

    # [Command line github-oauth](https://getcomposer.org/doc/articles/authentication-for-private-packages.md#command-line-github-oauth)
    composer config -g github-oauth.github.com "${GITHUB_API_TOKEN}"

    composer install \
        --prefer-dist \
        --no-dev \
        --no-interaction \
        --classmap-authoritative

    chown --verbose -R "${PROJECT_OWNER_UID}:${PROJECT_OWNER_GID}" /scripts

    chmod     o-rwx /scripts
    chmod -R ug+rx  /scripts
    chmod -R  u+w   /scripts

    if [ ! -e '/templates/www.conf.dist' ];
    then
        echo 'Missing configuration file.'

        return 1
    fi

    \cat '/templates/www.conf.dist' | \
    \sed -E 's/__SERVICE__/'"${PROJECT}"'/g' | \
    \sed -E 's/__UID__/'"${PROJECT_OWNER_UID}"'/g' | \
    \sed -E 's/__GID__/'"${PROJECT_OWNER_GID}"'/g' \
    > "${project_dir}/provisioning/containers/service/templates/www.conf" && \
    printf '%s.%s' 'Successfully copied php-fpm template' $'\n'

    echo '--- BEGIN ---'
    \cat '/templates/www.conf'
    echo '--- END ---'

    if [ ! -d "${project_dir}/.git" ];
    then

        rm --recursive --force --verbose "${project_dir}/.git"

    fi

    find "${project_dir}"  \
    -maxdepth 1 \
    -executable \
    -readable \
    -type d \
    -not -path "${project_dir}"'/provisioning/volumes' \
    -not -path "${project_dir}"'/public/emoji-data' \
    -exec sh -c "$(cat <<"EOF"
        \chown --recursive $2:$3  "$1" && \
        \chmod --recursive og-rwx "$1" && \
        \chmod --recursive  g+rx  "$1"
EOF
    )" shell {} "${PROJECT_OWNER_UID}" "${PROJECT_OWNER_GID}" \; && \
        printf '%s.%s' 'Successfully changed directories permissions' $'\n'

    find "${project_dir}" \
    -maxdepth 2 \
    -type d \
    -executable \
    -readable \
    -regex '.+/var.+' \
    -regex '.+/src/Media/Resources/.+.b64' \
    -not -path "${project_dir}"'/var/log' \
    -exec sh -c '\chmod --recursive ug+w "$1"' shell {} \; && \
        printf '%s.%s' 'Successfully made var directories writable' $'\n'

    find "${project_dir}" \
    -type f \
    -readable \
    -not -path "${project_dir}"'/var' \
    -not -path "${project_dir}"'/provisioning/volumes' \
    -not -path "${project_dir}"'/public/emoji-data' \
    -exec sh -c "$(cat <<"SCRIPT"
        \chown $2:$3  "$1" && \
        \chmod og-rwx "$1" && \
        \chmod  g+r   "$1"
SCRIPT
    )" shell {} "${PROJECT_OWNER_UID}" "${PROJECT_OWNER_GID}" \; && \
        printf '%s.%s' 'Successfully changed files permissions' $'\n'

    find "${project_dir}"  \
    -type f \
    -not -path "${project_dir}"'/bin' \
    -not -path "${project_dir}"'/provisioning/volumes' \
    -not -path "${project_dir}"'/public/emoji-data' \
    -exec sh -c "$(cat <<"SCRIPT"
        \chown --recursive $2:$3 "$1" && \
        \chmod --recursive ug+x  "$1"
SCRIPT
    )" shell {} "${PROJECT_OWNER_UID}" "${PROJECT_OWNER_GID}" \; && \
        printf '%s.%s' 'Successfully changed binaries permissions' $'\n'
}

set -Eeuo pipefail

