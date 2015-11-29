#!/bin/bash

source /vagrant/config/export-development-environment-variables.sh && \
/var/deploy/devobs/current/app/console wtw:job:run -e prod 2>> /var/log/job.run.error.log >> /var/log/job.run.out.log
