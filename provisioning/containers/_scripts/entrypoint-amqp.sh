#!/usr/bin/env bash
set -Eeuo pipefail

function start() {
  bash -c "/usr/local/bin/docker-entrypoint.sh ${1}" &

  sleep 10

  if [ $(rabbitmqctl list_users | grep -c "${DD_AMQP_USER}") -eq 0 ];
  then

    rabbitmqctl add_user "${DD_AMQP_USER}" "${DD_AMQP_PASSWORD}"

    rabbitmqctl set_permissions \
        -p '/' \
        "${DD_AMQP_USER}" \
        "^aliveness-test$" \
        "^amq\.default$" ".*"

    rabbitmqctl set_user_tags "${DD_AMQP_USER}" "${DD_AMQP_TAG}"

  fi

  tail -F /dev/null
}
start "$@"

set +Eeuo pipefail
