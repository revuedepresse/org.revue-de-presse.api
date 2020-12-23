# DevObs

[![Codeship Status for thierrymarianne/devobs-api](https://app.codeship.com/projects/beea8780-6695-0137-8a94-5e66d93e8e29/status?branch=main)](https://app.codeship.com/projects/345349)

Easing observation of Twitter lists related to software development

## Installation

The shell scripts written to install the project dependencies
have been tested under Ubuntu 20.04.

### Requirements

Install git by following instructions from the [official documentation](https://git-scm.org/).

Install mkcert by folllowing instructions from [https://mkcert.dev/](https://mkcert.dev/)

Install Docker by following instructions from the [official documentation](https://docs.docker.com/install/linux/docker-ce/ubuntu/).

Install Docker compose by following instructions from the [official documentation](https://docs.docker.com/compose/install/).

Intall all PHP vendors

```shell
make install-php-dependencies
```

Generate TLS certificates

```shell
make install-local-ca-store
make generate-development-tls-certificate-and-key
```

Build Docker images

```shell
make build-stack-images
```

## Run development stack

```shell
make run-stack
make set-up-amqp-queues
```

## Run test suites

Create test database

```shell
# requires granting privileges to a test user
# See provisioning/containers/postgres/templates/grant_privileges.sql
make create-test-database
```

Run unit tests with PHPUnit 

```shell
make run-php-unit-tests
```

Run features tests with Behat

```shell
make run-php-features-tests
``` 