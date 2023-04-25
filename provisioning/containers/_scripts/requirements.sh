#!/usr/bin/env bash
set -Eeuo pipefail

function add_system_user_group() {
    # shellcheck disable=SC2046
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

    chown "$WORKER_OWNER_UID.${WORKER_OWNER_GID}" "${HOME}/.blackfire.ini" || echo 'Could not change blackfire configuration file permissions' 1>&2
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

function install_php_extensions() {
    docker-php-ext-install \
        bcmath \
        intl \
        pcntl \
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

    wget https://github.com/DataDog/dd-trace-php/archive/0.86.3.tar.gz \
    --output-document=/tmp/datadog-php-tracer.tar.gz

    cd /tmp || exit
    tar -xvzf /tmp/datadog-php-tracer.tar.gz
    cd dd-trace-php-0.86.3 || exit
        phpize .
        ./configure --with-php-config="$(which php-config)"
        make
        make install

    wget https://pecl.php.net/get/amqp-1.11.0.tgz \
    --output-document /tmp/amqp-1.11.0.tgz && \
    cd /tmp && tar -xvzf /tmp/amqp-1.11.0.tgz && cd amqp-1.11.0 && \
    phpize .
    ./configure --with-php-config="$(which php-config)"
    make
    make install
}

function install_process_manager() {
    local nvm_dir
    nvm_dir="${1}"

    if [ -z "${nvm_dir}" ];
    then

        printf 'A %s is expected as %s (%s).%s' 'non-empty string' '1st argument' 'extendable version manager (asdf dir)' $'\n'

        return 1

    else

        rm -rf "${nvm_dir}"

    fi

    export NVM_DIR="$nvm_dir/.nvm" && (
      git clone https://github.com/nvm-sh/nvm.git "$NVM_DIR"
      cd "$NVM_DIR"
      git checkout "$(git describe --abbrev=0 --tags --match "v[0-9]*" "$(git rev-list --tags --max-count=1)")"
    ) && \. "$NVM_DIR/nvm.sh"

    echo 'export NVM_DIR='"${NVM_DIR}"'/.nvm'                                 >> "${HOME}/.bashrc"
    echo '[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" # This loads nvm'  >> "${HOME}/.bashrc"
    echo '[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"' >> "${HOME}/.bashrc"
    echo '16.15.1'                                                            >> "${HOME}/.nvmrc"

    source "${HOME}/.bashrc"

    nvm install 16.15.1
    nvm which 16.15.1
    nvm use node

    # [npm Config Setting](https://docs.npmjs.com/cli/v8/using-npm/config#cache)
    npm config set cache "${nvm_dir}/../npm" --global
    npm install pm2
    ./node_modules/.bin/pm2 install pm2-logrotate

    echo '' > ./.pm2-installed
}

function upgrade_packages_source() {
    echo 'Acquire::Retries "3";' > /etc/apt/apt.conf.d/80-retries

    apt update  --assume-yes
    apt upgrade --assume-yes
}

function install_process_manager_packages() {
    upgrade_packages_source

    # Install packages with package management system frontend (apt)
    apt-get install \
        --assume-yes \
        --no-install-recommends \
        curl \
        gawk \
        git \
        gpg \
        dirmngr
}

function install_process_manager_requirements() {
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
    apt-get install \
        --assume-yes \
        --no-install-recommends \
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

    apt-get install \
        --assume-yes \
        --no-install-recommends \
        libcurl4-gnutls-dev \
        libicu-dev \
        libfreetype-dev \
        libicu-dev \
        libjpeg-dev \
        libpng-dev \
        libwebp-dev \
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

set +Eeuo pipefail

