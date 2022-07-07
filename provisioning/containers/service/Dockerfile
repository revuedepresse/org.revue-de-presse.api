FROM        php:8.1-fpm-bullseye

ARG         WORKER_UID=1000
ARG         WORKER_GID=101

HEALTHCHECK CMD /bin/bash -c "pgrep php-fpm || ( test $(ps ax | \grep -E '\/dev\/null' -c) -gt 0 )"

COPY        --chown=${WORKER_UID}:${WORKER_GID} \
            ./_scripts \
            /scripts

COPY        --chown=${WORKER_UID}:${WORKER_GID} \
            ./service/templates \
            /templates

RUN         /bin/bash -c 'source /scripts/install-service-requirements.sh'

COPY        --from=composer:2.3 /usr/bin/composer /usr/bin/composer

USER        ${WORKER_UID}:${WORKER_GID}

VOLUME      ["/scripts", "/var/www/shared", "/var/www/revue-de-presse.org"]

WORKDIR     '/var/www/revue-de-presse.org'

ENTRYPOINT  ["/usr/bin/tini", "--"]

CMD         ["/usr/local/sbin/php-fpm"]
