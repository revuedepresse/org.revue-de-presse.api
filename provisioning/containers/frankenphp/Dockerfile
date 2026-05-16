# Production-style FrankenPHP runtime image.
#
# Not built by default. Build with:
#   docker compose --profile frankenphp build frankenphp
#
# Reuses the same extension-install path as the FPM service image —
# provisioning/containers/_scripts/requirements.sh is sourced here so the
# system packages, the docker-php-ext-install set, and the apt-clean step
# are byte-for-byte identical to install-service-requirements.sh's flow.
# Only the runtime substrate (Caddy + libphp worker mode) and the opcache /
# JIT tuning differ from the FPM service; all other PHP runtime values mirror
# the www.conf.dist template the FPM pool uses.

FROM dunglas/frankenphp:1-php8.5-bookworm

ARG OWNER_GID
ARG OWNER_UID
ARG PROJECT

RUN test -n "${OWNER_GID}" && \
    test -n "${OWNER_UID}" && \
    test -n "${PROJECT}"

ENV PROJECT_OWNER_GID=${OWNER_GID}
ENV PROJECT_OWNER_UID=${OWNER_UID}
ENV PROJECT=${PROJECT}
ENV PROJECT_DIR="/var/www/${PROJECT}"

# Reuse the FPM image's install path. requirements.sh defines:
#   - add_system_user_group           (ownership-matching service user)
#   - install_system_packages         (apt dev libs)
#   - install_php_extensions          (docker-php-ext-install bcmath / intl /
#                                      pcntl / pdo_mysql / pdo_pgsql / sockets /
#                                      sodium / mysqli / gd; opcache enabled;
#                                      Xdebug built from source but only
#                                      loaded when the ini opts in)
#   - clear_package_management_system_cache  (apt cleanup, smaller image)
COPY ./_scripts/requirements.sh /tmp/requirements.sh

RUN /bin/bash -c 'source /tmp/requirements.sh && \
        add_system_user_group && \
        install_system_packages && \
        install_php_extensions && \
        clear_package_management_system_cache' && \
    rm -f /tmp/requirements.sh

# PHP runtime configuration lives in
# provisioning/containers/frankenphp/templates/php.ini.dist
# and is mounted at runtime by compose at
# /usr/local/etc/php/conf.d/zz-frankenphp-prod.ini — see docker-compose.yaml.
# Mounting (not baking) matches the pattern the FPM service already uses
# for its extensions.ini.dist / www.conf templates, so edits don't require
# a rebuild.

# Assert the required extension set at build time. Mirrors the guard added to
# requirements.sh::install_php_extensions so a silent install regression
# fails the image build rather than blowing up at first request.
RUN for ext in sodium pdo_pgsql intl sockets pcntl; do \
        php -m | grep -qiE "^${ext}$" || { \
            echo "❌ Required PHP extension missing after FrankenPHP build: ${ext}" >&2; \
            exit 1; \
        } ; \
    done

# Caddyfile is mounted by compose at /etc/caddy/Caddyfile (read-only).
# Project source tree is mounted by compose at ${PROJECT_DIR}.

WORKDIR "${PROJECT_DIR}"

EXPOSE 443/tcp 443/udp 80/tcp

# Default entrypoint (caddy run with FrankenPHP module) comes from the base
# image. SERVER_NAME and FRANKENPHP_CONFIG are supplied via compose `environment`.
