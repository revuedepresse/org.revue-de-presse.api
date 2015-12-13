#!/bin/bash

TASK_NAME='consume-user-status-messages'
ERROR_LOG=/var/log/supervisor/$TASK_NAME.error.log
OUT_LOG=/var/log/supervisor/$TASK_NAME.out.log
TASK_OWNER=`whoami`

# ensure no similar script remains
pids=`ps ux | grep "rabbitmq:consumer" | grep -v grep | cut -d ' ' -f 2-3`;
for pid in $pids; do ((test 0${pid//[[:space:]]/} -gt 0) && (kill -3 $pid >> /dev/null 2>> /dev/null) && echo 'just killed process of pid "'$pid'" consuming messages') || echo 'no message consuming process already running'; done;

# Ensure required log files exist and
# necessary permissions have been applied to them for the current task owner
test -e $ERROR_LOG || sudo /bin/bash -c 'touch '$ERROR_LOG' && chown '$TASK_OWNER' '$ERROR_LOG
test -e $OUT_LOG || sudo /bin/bash -c 'touch '$OUT_LOG' && chown '$TASK_OWNER' '$OUT_LOG

command=`source /var/deploy/devobs/current/bin/get-consume-user-status-messages-command`
/bin/bash -c "$command"
