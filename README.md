# Press review

[![Build Status](https://travis-ci.org/thierrymarianne/daily-press-review.svg?branch=master)](https://travis-ci.org/thierrymarianne/daily-press-review)

[ ![Codeship Status for thierrymarianne/daily-press-review](https://app.codeship.com/projects/24369620-8f96-0136-7068-0e8ef5ba2310/status?branch=master)](https://app.codeship.com/projects/304052)

Easing observation of Twitter lists to publish a daily press review

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

## Usage

Run MySQL container

```
make run-mysql-container
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

## Testing

Create the test database schema

```
make create-database-schema-test
``` 

Run unit tests with PHPUnit 

```
make run-php-unit-tests
```
