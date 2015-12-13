#!/bin/bash

PROJECT_DIR=/var/deploy/devobs/current
USERS=`/bin/bash -c "source $PROJECT_DIR/bin/select-users-with-connected-accounts"`
TASK_NAME='produce-user-status-messages'
TASK_OWNER=`whoami`

ERROR_LOG=/var/log/job.$TASK_NAME.error.log
OUT_LOG=/var/log/job.$TASK_NAME.out.log

# Ensure required log files exist and
# necessary permissions have been applied to them for the current task owner
test -e $ERROR_LOG || sudo /bin/bash -c 'touch '$ERROR_LOG' && chown '$TASK_OWNER' '$ERROR_LOG
test -e $OUT_LOG || sudo /bin/bash -c 'touch '$OUT_LOG' && chown '$TASK_OWNER' '$OUT_LOG

for user in $USERS;
do
    command=`/bin/bash -c 'source '$PROJECT_DIR'/bin/get-produce-user-status-messages-command '$PROJECT_DIR' '$user' '$TASK_NAME` && \
    echo -e "[ executing \"$TASK_NAME\" ]\n"$command && \
    /bin/bash -c "$command" 2>> $ERROR_LOG >> $OUT_LOG;
done;

