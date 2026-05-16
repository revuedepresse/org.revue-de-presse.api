# Production-style FrankenPHP runtime image.
#
# Not built by default — this Dockerfile is only referenced from the compose
# service named `frankenphp`, which lives under the `frankenphp` profile.
# Build with:
#   docker compose --profile frankenphp build frankenphp
# Start with:
#   docker compose --profile frankenphp up frankenphp
#
# Replaces the (nginx + php-fpm) duo with a single Caddy-embedded PHP worker
# server. Designed to match the production posture: TLS-only listener, JSON
# access logs, opcache + JIT tuned for worker mode, locked-down user, no
# interactive Caddy admin endpoint.

FROM dunglas/frankenphp:1-php8.4-bookworm

ARG OWNER_GID
ARG OWNER_UID
ARG PROJECT

RUN test -n "${OWNER_GID}" && \
    test -n "${OWNER_UID}" && \
    test -n "${PROJECT}"

ENV PROJECT_OWNER_GID=${OWNER_GID}
ENV PROJECT_OWNER_UID=${OWNER_UID}
ENV PROJECT_DIR="/var/www/${PROJECT}"

# FrankenPHP's base image ships sodium / opcache / intl. We still need
# pdo_pgsql, pcntl, sockets to match the FPM service's extension set.
RUN install-php-extensions \
        pcntl \
        pdo_pgsql \
        sockets

# Assert the extension set so build fails loudly if mlocati/extension-installer
# silently dropped one — mirrors the guard in provisioning/_scripts/requirements.sh.
RUN for ext in sodium pdo_pgsql intl sockets pcntl; do \
        php -m | grep -qiE "^${ext}$" || { \
            echo "Required PHP extension missing: ${ext}" >&2; exit 1; \
        } ; \
    done

# Production php.ini baseline.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    { \
        echo "memory_limit=512M"; \
        echo "opcache.enable=1"; \
        echo "opcache.enable_cli=1"; \
        echo "opcache.memory_consumption=256"; \
        echo "opcache.max_accelerated_files=20000"; \
        echo "opcache.validate_timestamps=0"; \
        echo "opcache.jit=tracing"; \
        echo "opcache.jit_buffer_size=128M"; \
        echo "realpath_cache_size=4096K"; \
        echo "realpath_cache_ttl=600"; \
        echo "expose_php=Off"; \
    } > "$PHP_INI_DIR/conf.d/zz-frankenphp-prod.ini"

# Caddyfile is mounted by compose at /etc/caddy/Caddyfile (read-only).
# Project source tree is mounted by compose at ${PROJECT_DIR}.

WORKDIR "${PROJECT_DIR}"

EXPOSE 443/tcp 443/udp 80/tcp

# FrankenPHP's default entrypoint (caddy run) is correct for this service.
