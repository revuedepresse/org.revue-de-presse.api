# Commands

## How to start worker and amqp containers?

```shell
make start
```

## How to set up AMQP queues?

```shell
make set-up-amqp-queues
```

## How to list AMQP queues?

```shell
make list-amqp-queues
```

## How to import a Twitter list in command-line?

```shell
docker exec -ti $(docker ps -a| \grep --fixed-strings "${PROJECT_NAME}_worker" | awk '{print $1}') bash

LIST_NAME='Women in Data Science'
USER_NAME='BecomingDataSci'
php ./bin/console devobs:import-publishers-lists --list-restriction="${LIST_NAME}" "${USER_NAME}"
```

List created by [Renee Marie Parilak Teate](https://twitter.com/BecomingDataSci)  

