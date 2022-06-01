# [PHP 8.1 Bullseye](https://hub.docker.com/_/php?tab=tags&page=1&name=8.1-bullseye)
FROM php:8.1-bullseye

ARG WORKER_UID=1000
ARG WORKER_GID=101

COPY --chown=${WORKER_UID}:${WORKER_GID} ./_scripts             /scripts
COPY --chown=${WORKER_UID}:${WORKER_GID} ./service/templates    /templates

RUN /bin/bash -c 'source /scripts/install-worker-requirements.sh'

# [Composer 2.3](https://hub.docker.com/_/composer?tab=tags&page=1&name=2.3)
COPY --from=composer:2.3 /usr/bin/composer /usr/bin/composer

USER ${WORKER_UID}:${WORKER_GID}

VOLUME ["/scripts", "/var/www/revue-de-presse.org", "/var/www/shared"]

WORKDIR '/var/www/revue-de-presse.org'

CMD ["tail", "-f", "/dev/null"]
