# Devobs

[![Build Status](https://travis-ci.org/thierrymarianne/devobs-api.svg?branch=master)](https://travis-ci.org/thierrymarianne/devobs-api)

[![Codeship Status for thierrymarianne/devobs-api](https://app.codeship.com/projects/beea8780-6695-0137-8a94-5e66d93e8e29/status?branch=master)](https://app.codeship.com/projects/345349)

Easing observation of Twitter lists related to software development

## Installation

The shell scripts written to install the project dependencies have been tested under Ubuntu 16.04.5 LTS.
My guess about running them from another OS would be that it simply won't terminate as expected.

### Requirements

Install git by following instructions from the [official documentation](https://git-scm.org/).

Install Docker by following instructions from the [official documentation](https://docs.docker.com/install/linux/docker-ce/ubuntu/).

### PHP

Build a PHP container image

```
make build-php-container
```

### MySQL

Initialize MySQL from `app/config/parameters.yml`

```
# Provide with access to a shell in a mysql container 
# where access have been granted from credentials in parameters.yml
make initialize-mysql-volume
```

```
# Generate queries to be executed
# when the project data model has been modified
make diff-schema
```

### RabbitMQ

Configure RabbitMQ privileges

```
make configure-rabbitmq-user-privileges
```

Set up AMQP fabric

```
make setup-amqp-fabric
```

List AMQP messages

```
make list-amqp-messages
```

## Running containers

Run MySQL container

```
make run-mysql-container
```

Run Redis container

```
make run-redis-container
```

Run RabbitMQ container

```
make run-rabbitmq-container
```

Produce messages from lists of members

```
make produce-amqp-messages-from-members-lists
```

Consume Twitter API from messages

```
make consume-twitter-api-messages
```

## Available commands

Add members to a list

```
app/console add-members-to-aggregate -e prod \
--member-name="username-of-list-owner" \
--aggregate-name="list-name" \
--member-list="member-username"
```

Import subscriptions related to a list

```
app/console import-aggregates -e prod \
--member-name="username-of-list-owner" \
--find-ownerships
```

Add members from a list to another list 
(requires importing source list beforehand)

```
app/console add-members-to-aggregate -e prod \
--member-name="username-of-list-owner" \
--aggregate-name="name-of-destination-list" 
--list="name-of-source-list"
```

## Testing

Create the test database schema

```
make create-database-schema-test
``` 

Run unit tests with PHPUnit 

```
make run-php-unit-tests
```
