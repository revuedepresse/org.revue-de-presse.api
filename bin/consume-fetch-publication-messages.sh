#!/usr/bin/env bash
set -Eeo pipefail

if [ -n "${PROJECT_DIR}" ];
then
    current_directory="${PROJECT_DIR}/bin"
else
    current_directory="$(dirname "$0")"
fi

source "${current_directory}/console.sh"

consume_fetch_publication_messages

set +Eeo pipefail
