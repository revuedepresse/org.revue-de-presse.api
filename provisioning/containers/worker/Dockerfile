FROM php:8.1-cli-bullseye

ENV WORKER='org.example.worker'

ARG WORKER_UID=1000
ARG WORKER_GID=101

ARG WORKER_DIR="${WORKER}"

HEALTHCHECK CMD /bin/bash -c "test $(ps ax | \grep -E '\/dev\/null' -c) -gt 0"

COPY --chown=${WORKER_UID}:${WORKER_GID} ./_scripts                 /scripts

COPY --chown=${WORKER_UID}:${WORKER_GID} ./_scripts/cmd-worker.sh   /start.sh
COPY --chown=${WORKER_UID}:${WORKER_GID} ./worker/templates         /templates

COPY --from=composer:2.3 /usr/bin/composer /usr/bin/composer

RUN         /bin/bash -c 'source /scripts/install-shared-requirements.sh' && \
            /bin/bash -c 'source /scripts/install-worker-requirements.sh'

USER        ${WORKER_UID}:${WORKER_GID}

VOLUME      ["/scripts", "/var/www/${WORKER_DIR}", "/var/www/shared"]

WORKDIR     "/var/www/${WORKER_DIR}"

ENTRYPOINT  ["/usr/bin/tini", "--"]

CMD         ["/start.sh"]
