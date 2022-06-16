FROM node:16.15.1-bullseye-slim

ENV _WORKER='worker'

ARG WORKER_UID=1000
ARG WORKER_GID=101
ARG WORKER="${_WORKER}"

COPY --chown=${WORKER_UID}:${WORKER_GID} ./_scripts                                 /scripts

COPY --chown=${WORKER_UID}:${WORKER_GID} ./_scripts/cmd-process-manager.sh          /start.sh
COPY --chown=${WORKER_UID}:${WORKER_GID} ./_scripts/entrypoint-process-manager.sh   /entrypoint.sh

HEALTHCHECK CMD /bin/bash -c "pgrep node || ( test $(ps ax | \grep -E '\/dev\/null' -c) -gt 0 )"

RUN         /bin/bash -c 'source /scripts/install-process-manager-requirements.sh'

VOLUME      ["/scripts", "/var/www/${WORKER}", "/var/www/shared"]

WORKDIR     "/var/www/${WORKER}"

USER        ${WORKER_UID}:${WORKER_GID}

ENTRYPOINT  ["/usr/bin/tini", "--"]

CMD         ["/entrypoint.sh"]
