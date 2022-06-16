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

## How to start a process manager container for consuming AMQP messages?

```shell
# Customize the WORKER variable value assignment
WORKER='worker.example.org' \
make start-process-manager
```

## How to dispatch AMQP messages so that member publications are fetched?

```shell
LIST_NAME='W3C-Groups' \
USERNAME="w3c" \
make dispatch-fetch-publications-messages
```

## How to import a Twitter list in command-line?

```shell
# From host machine where docker engine has been installed
docker exec -ti $(docker ps -a| \grep --fixed-strings "${PROJECT_NAME}_worker" | awk '{print $1}') bash

# In worker container
LIST_NAME='w3cstaff' \
USERNAME='w3c' \
php ./bin/console devobs:import-publishers-lists --list-restriction="${LIST_NAME}" "${USERNAME}"
```

List created by [Renee Marie Parilak Teate](https://twitter.com/BecomingDataSci)  

