FROM        rabbitmq:3.11-management

ARG         DD_AMQP_USER
ARG         DD_AMQP_PASSWORD

ARG         DD_AMQP_TAG

ENV         DD_AMQP_USER=${DD_AMQP_USER}
ENV         DD_AMQP_PASSWORD=${DD_AMQP_PASSWORD}

ENV         DD_AMQP_TAG=${DD_AMQP_TAG}

EXPOSE      4369 5671 5672 25672

COPY        ./_scripts/entrypoint-amqp.sh   /entrypoint.sh

RUN         chmod a+x /entrypoint.sh

ENTRYPOINT  ["/entrypoint.sh"]

CMD         ["rabbitmq-server"]
