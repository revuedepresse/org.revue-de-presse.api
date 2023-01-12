#!/usr/bin/env bash
set -Eeuo pipefail

function start() {
    php -i >> /dev/null 2>&1

    if [ $? -eq 0 ];
    then

        /usr/local/sbin/php-fpm

    else

        printf 'Invalid %s ("%s").%s' 'SAPI configuration' 'PHP FPM' $'\n' 1>&2

        return 1

    fi
}
start

set +Eeuo pipefail
