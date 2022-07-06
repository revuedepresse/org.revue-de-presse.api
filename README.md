# Revue-de-presse worker

![api.revue-de-presse.org continuous integration](https://github.com/thierrymarianne/api.revue-de-presse.org/actions/workflows/continuous-integration.yml/badge.svg)

Worker collecting publications from social media (Twitter) and [public lists](https://help.twitter.com/en/using-twitter/twitter-lists).

API serving daily short lists (10 items) of top news in France sorted by popularity.

See [revue-de-presse.org](https://revue-de-presse.org).

## Installation

The shell scripts written for bash   
have been tested with Ubuntu 22.04 (`Jammy Jellyfish`).

### Requirements

Install [git](https://git-scm.com/downloads).
> Git is a free and open source distributed version control system designed 
> to handle everything from small to very large projects with speed and efficiency.

Install [Docker Docker Engine](https://docs.docker.com/engine/install/).
> Docker Engine is an open source containerization technology for building and containerizing your applications.

Install [Docker Compose](https://docs.docker.com/compose/install/).
> Compose is a tool for defining and running multi-container Docker applications.

Install [jq](https://stedolan.github.io/jq/download/).
> jq is a lightweight and flexible command-line JSON processor.

### Documentation

```
make help
```

## License

GNU General Public License v3.0 or later

See [COPYING](./COPYING) to see the full text.

## Acknowledgment

We're grateful towards all amazing contributors involved in the following  
communities, organizations and projects (in lexicographic order):

 - [Blackfire](https://blackfire.io)
 - [Composer](http://getcomposer.org/)
 - [Datadog](https://datadoghq.eu/)
 - [Debian](https://www.debian.org/)
 - [Docker](docker.com)
 - [Doctrine](https://www.doctrine-project.org/)
 - [GitHub](https://github.com/)
 - [PHP](https://www.php.net/)
 - [Symfony](https://symfony.com/)
 - [Ubuntu](https://ubuntu.com/)

### Some of their logos

[![Blackfire](../worker/doc/images/blackfire-io.png?raw=true)](https://blackfire.io)  
