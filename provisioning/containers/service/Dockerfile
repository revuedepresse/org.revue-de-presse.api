FROM        php:8.2-fpm-bullseye

HEALTHCHECK CMD /bin/bash -c "pgrep php-fpm || ( test $(ps ax | \grep -E '\/dev\/null' -c) -gt 0 )"

ARG         OWNER_GID
ARG         OWNER_UID
ARG         PROJECT

RUN         test -n "${OWNER_GID}" && \
            test -n "${OWNER_UID}" && \
            test -n "${PROJECT}"

ENV         PROJECT_OWNER_GID=${OWNER_GID}
ENV         PROJECT_OWNER_UID=${OWNER_UID}

ARG         PROJECT_DIR="${PROJECT:-org.example.api}"

COPY        --chown=${PROJECT_OWNER_UID}:${PROJECT_OWNER_GID} \
            ./_scripts \
            /scripts

COPY        --chown=${PROJECT_OWNER_UID}:${PROJECT_OWNER_GID} \
            ./service/templates \
            /templates

COPY        --chown=${PROJECT_OWNER_UID}:${PROJECT_OWNER_GID} \
            ./_scripts/cmd-service.sh  \
            /start.sh

COPY        --from=composer:2.5 /usr/bin/composer /usr/bin/composer

RUN         /bin/bash -c 'source /scripts/install-service-requirements.sh'

USER        ${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID}

VOLUME      ["/scripts", "/var/www/shared", "/var/www/${PROJECT_DIR}"]

WORKDIR     "/var/www/${PROJECT_DIR}"

ENTRYPOINT  ["/usr/bin/tini", "--"]

CMD         ["/start.sh"]
