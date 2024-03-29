# Commands

## How to authorize an existing application to access publications on behalf of a Twitter member?

```shell
make shell-worker

# Follow instructions to authorize an Twitter app
# to collect publications from lists on behalf of a Twitter member  
bin/console app:authorize-application
```

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
make start
```

## How to dispatch AMQP messages so that member publications are fetched?

```shell
LIST_NAME='W3C-Groups' \
USERNAME="w3c" \
make dispatch-fetch-tweets-amqp-messages
```

## How to import a Twitter list in command-line?

```shell
# From host machine where docker engine has been installed
docker exec -ti $(docker ps -a| \grep --fixed-strings "${PROJECT_NAME}_worker" | awk '{print $1}') bash

# In worker container
LIST_NAME='w3cstaff' \
USERNAME='w3c' \
php ./bin/console app:synchronize-member-lists --single-list-filter="${LIST_NAME}" "${USERNAME}"
```
