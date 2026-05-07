# Revue de presse

:gb: [Revue-de-presse.org](https://github.com/revuedepresse) serves press titles curated daily from official French Media Twitter accounts.

All development is delivered under free and open-source software licence.

:fr: [Revue-de-presse.org](https://github.com/revuedepresse) est un projet citoyen indépendant qui s'adresse à toute personne curieuse de l'actualité et de l'influence des médias sur l'opinion.


![revue-de-presse.org continuous integration](https://github.com/thierrymarianne/api.revue-de-presse.org/actions/workflows/continuous-integration.yml/badge.svg)

## HTTP API

API serving daily short lists (10 items) of top news in France sorted by popularity.  
Said popularity is simply based on retweets fetched by calling Twitter APIs.

## Installation

The shell scripts written for bash   
have been tested extensively with [Ubuntu 24.04 (`Noble Numbat`)](https://documentation.ubuntu.com/release-notes/24.04/4/)  
and [Debian 11 (`Bullseye`)](https://www.debian.org/releases/bullseye/)

### Requirements

Install git by following instructions from the [official documentation](https://git-scm.org/).

Install Docker by following instructions from the [official documentation](https://docs.docker.com/install/linux/docker-ce/ubuntu/).

Install Docker compose by following instructions from the [official documentation](https://docs.docker.com/compose/install/).

### Documentation

```
make help
```

Build application

```
export COMPOSE_PROJECT_NAME='org_revue-de-presse_api' \
SERVICE='org.revue-de-presse.api' && \
make build
```

Start application

```
export COMPOSE_PROJECT_NAME='org_revue-de-presse_api' \
SERVICE='org.revue-de-presse.api' && \
make start
```

Execute shell in service container

```
docker exec -ti $(docker ps -a | grep 'api[-]service' | awk '{print $1}') bash
```

## License

GNU General Public License v3.0 or later

See [COPYING](./COPYING) to see the full text.

## Acknowledgment

We're grateful towards all amazing contributors involved in the following  
communities, organizations and projects (in lexicographic order):

- [Composer](http://getcomposer.org/)
- [Debian](https://www.debian.org/)
- [Docker](docker.com)
- [Doctrine](https://www.doctrine-project.org/)
- [GitHub](https://github.com/)
- [PHP](https://www.php.net/)
- [Symfony](https://symfony.com/)
- [Ubuntu](https://ubuntu.com/)
