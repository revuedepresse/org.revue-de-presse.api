FROM        php:8.2-cli-bullseye

ARG         OWNER_UID
ARG         OWNER_GID
ARG         WORKER

RUN         test -n "${OWNER_UID}"
RUN         test -n "${OWNER_GID}"
RUN         test -n "${WORKER}"

ENV         WORKER_OWNER_UID=${OWNER_UID}
ENV         WORKER_OWNER_GID=${OWNER_GID}

ARG         WORKER_DIR="${WORKER}"

HEALTHCHECK CMD /bin/bash -c "test $(ps ax | \grep -E '\/dev\/null' -c) -gt 0"

COPY        --chown=${WORKER_OWNER_UID}:${WORKER_OWNER_GID} ./_scripts                 /scripts

COPY        --chown=${WORKER_OWNER_UID}:${WORKER_OWNER_GID} ./_scripts/cmd-worker.sh   /start.sh
COPY        --chown=${WORKER_OWNER_UID}:${WORKER_OWNER_GID} ./worker/templates         /templates

COPY        --from=composer:2.5 /usr/bin/composer /usr/bin/composer

RUN         /bin/bash -c 'source /scripts/install-shared-requirements.sh' && \
            /bin/bash -c 'source /scripts/install-worker-requirements.sh'

USER        ${WORKER_OWNER_UID}:${WORKER_OWNER_GID}

VOLUME      ["/scripts", "/var/www/${WORKER_DIR}", "/var/www/shared"]

WORKDIR     "/var/www/${WORKER_DIR}"

ENTRYPOINT  ["/usr/bin/tini", "--"]

CMD         ["/start.sh"]
