FROM    php:8.1-cli-bullseye

ARG     OWNER_UID
ARG     OWNER_GID

RUN     test -n "${OWNER_UID}"
RUN     test -n "${OWNER_GID}"

ENV     SERVICE_OWNER_UID=${OWNER_UID}
ENV     SERVICE_OWNER_GID=${OWNER_GID}

ENV     SERVICE=${SERVICE:-org.revue-de-presse.api}

COPY    --chown=${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID} \
        ./_scripts \
        /scripts

COPY    --chown=${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID} \
        ./service/templates \
        /templates

RUN     /bin/bash -c 'source /scripts/install-service-requirements.sh'

COPY    --from=composer:2.3 /usr/bin/composer /usr/bin/composer

USER    ${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID}

VOLUME  ["/scripts", "/var/www/shared", "/var/www/${SERVICE}"]

WORKDIR "/var/www/${SERVICE}"

CMD     ["tail", "-F", "/dev/null"]
