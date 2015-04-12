#!/bin/zsh

## sudo -E crontab -e -u weaver
#  15 */2 * * * /opt/local/bin/produce_twitter_messages.sh wtw:amqp:tw:prd:utl thierrymarianne 2>> /var/log/cron.error.log >> /var/log/cron.out.log &
#  15 */2 * * * /opt/local/bin/produce_twitter_messages.sh wtw:amqp:tw:prd:lm thierrymarianne 2>> /var/log/cron.error.log >> /var/log/cron.out.log &

if [ ${#1} -ne 0 ]
then
    COMMAND_NAME=$1
else
    if [ 0${COMMAND_NAME//[[:spaces:]]/} -eq 0 ]
    then
        COMMAND_NAME=wtw:amqp:tw:prd:utl
        echo '[command name] '$COMMAND_NAME
    fi
fi

if [ ${#2} -ne 0 ]
then
    SCREEN_NAME=$2
else
    if [ 0${SCREEN_NAME//[[:spaces:]]/} -eq 0 ]
    then
         SCREEN_NAME=## FILL ME ##
         TOKENS="--oauth_token=## FILL ME ## --oauth_secret=## FILL ME ##"
         echo '[default memory limit] '$SCREEN_NAME
         echo '[tokens] '$TOKENS
    fi
fi

if [ 0${PROJECT_DIR//[[:spaces:]]/} -eq 0 ]
then
     PROJECT_DIR=/var/www/weaving-the-web
     echo '[default project dir] '$PROJECT_DIR
fi

filepath="$(basename "$0")"
filename="${filepath%%.*}"

command='for i in `cat /vagrant/conf/weaving-the-web.properties`; do export $i; done; '
command=$command"/usr/bin/php $PROJECT_DIR/app/console $COMMAND_NAME --screen_name=$SCREEN_NAME $TOKENS"
echo 'Executing command: '$command
/bin/zsh -c $command >> /var/log/$filename.out.log 2>> /var/log/$filename.error.log
