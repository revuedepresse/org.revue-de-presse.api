#!/bin/bash

# To be sourced by user producing AMQP messages
# after having replaced member screen name placeholder ("jack")
# and having replaced list name placeholder ("jack")

function produce_amqp_messages_from_member_timeline {
    local member='jack'
    cd /var/www/devobs && \
    sudo -uwww-data /bin/bash -c "export SYMFONY_ENV='prod' PROJECT_DIR=`pwd` DOCKER_MODE=1 username='"${member}"' && make produce-amqp-messages-from-member-timeline"
}
alias produce-amqp-messages-from-member-timeline='produce_amqp_messages_from_member_timeline'

function produce_amqp_messages_from_members_lists {
    local member='jack'
    cd /var/www/devobs && \
    sudo -uwww-data /bin/bash -c "export SYMFONY_ENV='prod' PROJECT_DIR=`pwd` DOCKER_MODE=1 username='"${member}"' && make produce-amqp-messages-from-members-lists"
}
alias produce-amqp-messages-from-members-lists='produce_amqp_messages_from_members_lists'

function produce_amqp_messages_from_news_list {
    local member='jack'
    local list_name='my_list'
    cd /var/www/devobs && \
    sudo -uwww-data /bin/bash -c "export SYMFONY_ENV='prod' PROJECT_DIR=`pwd` DOCKER_MODE=1 username='"${member}"' list_name='"${list_name}"' && make produce-amqp-messages-from-news-lists"
}
alias produce-amqp-messages-from-news-lists='produce_amqp_messages_from_news_list'

function refresh_statuses {
    local aggregate_name='my_list'
    cd /var/www/devobs && \
    sudo -uwww-data /bin/bash -c "export SYMFONY_ENV='prod' PROJECT_DIR=`pwd` DOCKER_MODE=1 aggregate_name='"${aggregate_name}"' && make refresh-statuses"
}
alias refresh-statuses='refresh_statuses'

### Example of cron tab for user producing AMQP messages from Twitter API
#
# PROJECT_DIR=/var/www/devobs
# DOCKER_MODE=1
# username=jack
# list_mame=jack
# aggregate_mame=jack
#
#  0 */7 * * *   /bin/bash -c "cd ${PROJECT_DIR} && make produce-amqp-messages-from-members-lists"
#  */20 * * * *  /bin/bash -c "cd ${PROJECT_DIR} && export list_name=${list_mame} && make produce-amqp-messages-from-news-lists"
#  0 */7 * * *   /bin/bash -c "cd ${PROJECT_DIR} && make produce-amqp-messages-from-member-timeline"
#  0 23 * * *    /bin/bash -c "cd ${PROJECT_DIR} && make refresh-statuses"
#
