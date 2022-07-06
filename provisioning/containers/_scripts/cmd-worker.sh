#!/usr/bin/env bash
set -Eeuo pipefail

source '/scripts/requirements.sh'

function start() {
    php -i >> /dev/null 2>&1

    if [ $? -eq 0 ];
    then

        tail -f /dev/null

    else

        printf 'Invalid %s ("%s").%s' 'SAPI configuration' 'PHP CLI' $'\n' 1>&2

        return 1

    fi
}
start

set +Eeuo pipefail
