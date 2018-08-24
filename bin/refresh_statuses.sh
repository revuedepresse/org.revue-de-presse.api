#!/bin/bash

if [ ! -z "${PROJECT_DIR}" ];
then
    current_directory="${PROJECT_DIR}/bin"
else
    current_directory=`dirname "$0"`
fi

source "${current_directory}"/functions.sh

refresh_statuses
