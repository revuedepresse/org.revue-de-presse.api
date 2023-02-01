FROM    php:8.2-cli-bullseye

ARG     OWNER_UID
ARG     OWNER_GID
ARG     SERVICE

RUN     test -n "${OWNER_UID}" && \
        test -n "${OWNER_GID}" && \
        test -n "${SERVICE}"

ENV     SERVICE_DIR=${SERVICE}
ENV     SERVICE_OWNER_UID=${OWNER_UID}
ENV     SERVICE_OWNER_GID=${OWNER_GID}

COPY    --chown=${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID} \
        ./_scripts \
        /scripts

COPY    --chown=${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID} \
        ./service/templates \
        /templates

RUN     /bin/bash -c 'source /scripts/install-service-requirements.sh'

COPY    --from=composer:2.5 /usr/bin/composer /usr/bin/composer

USER    ${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID}

VOLUME  ["/scripts", "/var/www/shared", "/var/www/${SERVICE_DIR}"]

WORKDIR "/var/www/${SERVICE_DIR}"

CMD     ["tail", "-F", "/dev/null"]
