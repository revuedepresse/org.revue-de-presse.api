#!/bin/bash

if [ -n "${PROJECT_DIR}" ];
then
    current_directory="${PROJECT_DIR}"
else
    current_directory=`dirname "$0"`
fi

source "${current_directory}/fun.sh"

refresh_statuses
