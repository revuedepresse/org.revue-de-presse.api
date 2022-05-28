# Revue-de-presse.org

[![Codeship Status for thierrymarianne/daily-press-review](https://app.codeship.com/projects/24369620-8f96-0136-7068-0e8ef5ba2310/status?branch=main)](https://app.codeship.com/projects/304052)

API serving daily short lists (10 items) of top news in France sorted by popularity.
Their popularity is based on retweets only retrieved by using Twitter API.

## Installation

The shell scripts written for bash   
have been tested with Ubuntu 22.04 (`Jammy Jellyfish`).

### Requirements

Install git by following instructions from the [official documentation](https://git-scm.org/).

Install Docker by following instructions from the [official documentation](https://docs.docker.com/install/linux/docker-ce/ubuntu/).

Install Docker compose by following instructions from the [official documentation](https://docs.docker.com/compose/install/).

### Build docker images

```
make build
```

## Install application dependencies

```
make install
```

## Start service

```
make start
```

## Stop service

```
make stop
```

## Testing

```
make test
```
