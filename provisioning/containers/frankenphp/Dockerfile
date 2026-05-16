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

# PHP runtime configuration overlay. The shared block mirrors the
# www.conf.dist template that the FPM pool uses; the FrankenPHP-specific
# block beneath it tunes the long-lived worker runtime, which has different
# requirements from a per-request FPM child process.
RUN { \
        echo "; ---- shared with provisioning/containers/service/templates/www.conf.dist ----"; \
        echo "memory_limit                  = 64M"; \
        echo "error_reporting               = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED"; \
        echo "display_errors                = Off"; \
        echo "display_startup_errors        = Off"; \
        echo "html_errors                   = On"; \
        echo "log_errors                    = On"; \
        echo ""; \
        echo "; ---- FrankenPHP-specific (worker mode is long-lived; opcache + JIT pay off) ----"; \
        echo "opcache.enable                = 1"; \
        echo "opcache.enable_cli            = 1"; \
        echo "opcache.memory_consumption    = 256"; \
        echo "opcache.max_accelerated_files = 20000"; \
        echo "opcache.validate_timestamps   = 0"; \
        echo "opcache.jit                   = tracing"; \
        echo "opcache.jit_buffer_size       = 128M"; \
        echo "realpath_cache_size           = 4096K"; \
        echo "realpath_cache_ttl            = 600"; \
        echo "expose_php                    = Off"; \
    } > "$PHP_INI_DIR/conf.d/zz-frankenphp-prod.ini"

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
