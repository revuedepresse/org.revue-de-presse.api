#!/bin/bash

REQUIRED_FILES_EXIST=~vagrant/.ensure_required_files_exist

if [ -e $REQUIRED_FILES_EXIST ]
then
    echo 'This shell provisioning script (ensuring required files exist) was executed before.'
    exit 0
fi

function ensure_dir_exists {
    export SCRIPT=/tmp/ensure-directory-exists
    export FOLDER_PATH=$1

    cat > $SCRIPT <<SCRIPT
    echo 'Checking if "'$FOLDER_PATH'" directory exists.'
    if [ ! -d $FOLDER_PATH ]
    then
        echo 'Creating missing directory.'
        mkdir -p $FOLDER_PATH
    else
        echo 'Confirmed "'$FOLDER_PATH'" directory existed already.'
    fi
SCRIPT

    echo "Executing the following script:"
    echo "## --- START --- ##"
    cat $SCRIPT
    echo "## --- END ---##"

    sudo /bin/bash -c "source $SCRIPT"
}

export APACHE_HOME_DIR=/var/www
export COMPOSER_FOLDER=/home/vagrant/.composer
export DEPLOY_FOLDER=/var/deploy/devobs
export DOCUMENT_ROOT=/var/www/devobs
export LOG_PHP=/var/log/php

ensure_dir_exists $APACHE_HOME_DIR
ensure_dir_exists $COMPOSER_FOLDER
ensure_dir_exists $DEPLOY_FOLDER
ensure_dir_exists $LOG_PHP

echo "Checking if symbolic link exists."
if [ ! -L $DOCUMENT_ROOT ]
then
    echo "Creating missing symbolic link."
    ln -s $DEPLOY_FOLDER/current/web $DOCUMENT_ROOT
else
    echo "Confirmed symbolic link existed already."
fi

export LOG_RUN_JOB_ERROR=/var/log/job.run.error.log
export LOG_RUN_JOB_OUT=/var/log/job.run.out.log

touch $LOG_RUN_JOB_ERROR
touch $LOG_RUN_JOB_OUT

chown vagrant $LOG_RUN_JOB_ERROR
chown vagrant $LOG_RUN_JOB_OUT

chown -R vagrant $APACHE_HOME_DIR
chown -R vagrant $COMPOSER_FOLDER
chown -R vagrant $DEPLOY_FOLDER

touch $REQUIRED_FILES_EXIST
