#!/bin/bash

source /var/deploy/devobs/current/bin/export-config-parameters.sh

/var/deploy/devobs/current/app/console wtw:job:run -e prod 2>> /var/log/job.run.error.log >> /var/log/job.run.out.log
