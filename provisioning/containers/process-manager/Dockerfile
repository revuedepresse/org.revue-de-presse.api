FROM        php:8.2-rc-cli-bullseye

ENV         WORKER='org.example.worker'

ARG         WORKER_OWNER_UID=1000
ARG         WORKER_OWNER_GID=101

ARG         WORKER_DIR="${WORKER}"

HEALTHCHECK CMD /bin/bash -c "pgrep node || ( test $(ps ax | \grep -E '\/dev\/null' -c) -gt 0 )"

COPY        --chown=${WORKER_OWNER_UID}:${WORKER_OWNER_GID} ./_scripts                                 /scripts

COPY        --chown=${WORKER_OWNER_UID}:${WORKER_OWNER_GID} ./_scripts/cmd-process-manager.sh          /start.sh
COPY        --chown=${WORKER_OWNER_UID}:${WORKER_OWNER_GID} ./_scripts/entrypoint-process-manager.sh   /entrypoint.sh

COPY        --from=composer:2.3 /usr/bin/composer /usr/bin/composer

RUN         /bin/bash -c 'source /scripts/install-shared-requirements.sh'
RUN         /bin/bash -c 'source /scripts/install-process-manager-requirements.sh'

USER        ${WORKER_OWNER_UID}:${WORKER_OWNER_GID}

VOLUME      ["/scripts", "/var/www/${WORKER_DIR}", "/var/www/shared"]

WORKDIR     "/var/www/${WORKER_DIR}"

ENTRYPOINT  ["/usr/bin/tini", "--"]

CMD         ["/entrypoint.sh"]

