FROM    php:8.1-bullseye

ARG     WORKER_UID=1000
ARG     WORKER_GID=101

COPY    --chown=${WORKER_UID}:${WORKER_GID} \
        ./_scripts \
        /scripts

COPY    --chown=${WORKER_UID}:${WORKER_GID} \
        ./service/templates \
        /templates

RUN     /bin/bash -c 'source /scripts/install-service-requirements.sh'

COPY    --from=composer:2.3 /usr/bin/composer /usr/bin/composer

USER    ${WORKER_UID}:${WORKER_GID}

VOLUME  ['/scripts', '/var/www/shared', '/var/www/api.revue-de-presse.org']

WORKDIR '/var/www/api.revue-de-presse.org'

CMD     ['tail', '-f', '/dev/null']
