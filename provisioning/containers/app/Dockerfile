FROM    php:8.1-cli-bullseye

ARG     SERVICE_OWNER_UID=1000
ARG     SERVICE_OWNER_GID=101

COPY    --chown=${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID} \
        ./_scripts \
        /scripts

COPY    --chown=${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID} \
        ./service/templates \
        /templates

RUN     /bin/bash -c 'source /scripts/install-service-requirements.sh'

COPY    --from=composer:2.3 /usr/bin/composer /usr/bin/composer

USER    ${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID}

VOLUME  ["/scripts", "/var/www/shared", "/var/www/api.revue-de-presse.org"]

WORKDIR '/var/www/api.revue-de-presse.org'

CMD     ["tail", "-F", "/dev/null"]
