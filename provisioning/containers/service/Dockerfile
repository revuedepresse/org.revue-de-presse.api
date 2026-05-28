FROM        php:8.5-fpm-bookworm

HEALTHCHECK CMD /bin/bash -c "pgrep php-fpm || ( test $(ps ax | \grep -E '\/dev\/null' -c) -gt 0 )"

ARG         OWNER_GID
ARG         OWNER_UID
ARG         PROJECT
# HOST_OS conveys the developer's host OS to install scripts that
# would otherwise misbehave on Docker Desktop for macOS (chown/chmod
# against bind-mounted host paths). Defaults to empty when not passed.
ARG         HOST_OS=

RUN         test -n "${OWNER_GID}" && \
            test -n "${OWNER_UID}" && \
            test -n "${PROJECT}"

ENV         PROJECT_OWNER_GID=${OWNER_GID}
ENV         PROJECT_OWNER_UID=${OWNER_UID}
ENV         HOST_OS=${HOST_OS}

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

COPY        --from=composer:2.9 /usr/bin/composer /usr/bin/composer

RUN         /bin/bash -c 'source /scripts/install-service-requirements.sh'

USER        ${SERVICE_OWNER_UID}:${SERVICE_OWNER_GID}

VOLUME      ["/scripts", "/var/www/shared", "/var/www/${PROJECT_DIR}"]

WORKDIR     "/var/www/${PROJECT_DIR}"

ENTRYPOINT  ["/usr/bin/tini", "--"]

CMD         ["/start.sh"]
