FROM php:8.1-cli-bullseye

ENV _WORKER='worker'

ARG WORKER_UID=1000
ARG WORKER_GID=101

ARG WORKER="${_WORKER}"

HEALTHCHECK CMD /bin/bash -c "pgrep node || ( test $(ps ax | \grep -E '\/dev\/null' -c) -gt 0 )"

COPY --chown=${WORKER_UID}:${WORKER_GID} ./_scripts                                 /scripts

COPY --chown=${WORKER_UID}:${WORKER_GID} ./_scripts/cmd-process-manager.sh          /start.sh
COPY --chown=${WORKER_UID}:${WORKER_GID} ./_scripts/entrypoint-process-manager.sh   /entrypoint.sh

COPY --from=composer:2.3 /usr/bin/composer /usr/bin/composer

RUN         /bin/bash -c 'source /scripts/install-shared-requirements.sh'

RUN         /bin/bash -c 'source /scripts/install-process-manager-requirements.sh'

USER        ${WORKER_UID}:${WORKER_GID}

VOLUME      ["/scripts", "/var/www/${WORKER}", "/var/www/shared"]

WORKDIR     "/var/www/${WORKER}"

ENTRYPOINT  ["/usr/bin/tini", "--"]

CMD         ["/entrypoint.sh"]

