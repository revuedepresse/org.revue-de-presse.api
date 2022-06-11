# [PHP 8.1 FPM Bullseye](https://hub.docker.com/_/php?tab=tags&page=1&name=8.1-bullseye)
FROM php:8.1-fpm-bullseye

ENV _SERVICE='worker'

ARG WORKER_UID=1000
ARG WORKER_GID=101
ARG SERVICE="${_SERVICE}"

HEALTHCHECK CMD /bin/bash -c "test $(ps ax | \grep -E '\/dev\/null' -c) -gt 0"

COPY --chown=${WORKER_UID}:${WORKER_GID} ./_scripts                 /scripts
COPY --chown=${WORKER_UID}:${WORKER_GID} ./service/templates        /templates
COPY --chown=${WORKER_UID}:${WORKER_GID} ./_scripts/cmd-worker.sh   /start.sh

RUN /bin/bash -c 'source /scripts/install-worker-requirements.sh'

# [Composer 2.3](https://hub.docker.com/_/composer?tab=tags&page=1&name=2.3)
COPY --from=composer:2.3 /usr/bin/composer /usr/bin/composer

USER    ${WORKER_UID}:${WORKER_GID}

VOLUME  ["/scripts", "/var/www/${SERVICE}", "/var/www/shared"]

WORKDIR "/var/www/${SERVICE}"

ENTRYPOINT ["/usr/bin/tini", "--"]

CMD ["/start.sh"]
