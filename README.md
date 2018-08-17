# Status Review

Easing observation of Twitter lists

## Installation

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
make initialize-mysql-volume
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
run-rabbitmq-container
```

Produce messages from lists of members

```
make produce-amqp-messages-from-members-lists
```

Consume Twitter API from messages

```
make consume-twitter-api-messages
```

