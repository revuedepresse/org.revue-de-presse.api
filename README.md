
# Revue-de-presse.org

![revue-de-presse.org continuous integration](https://github.com/thierrymarianne/api.revue-de-presse.org/actions/workflows/continuous-integration.yml/badge.svg)

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

## License

GNU General Public License v3.0 or later

See [COPYING](./COPYING) to see the full text.
