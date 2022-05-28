# [PHP 8.1 Bullseye](https://hub.docker.com/_/php?tab=tags&page=1&name=8.1-bullseye)
FROM php:8.1-bullseye

RUN apt-get update && apt-get install -y wget unzip git libicu-dev librabbitmq-dev libcurl4-gnutls-dev && \
    docker-php-ext-install bcmath intl mysqli pcntl pdo_mysql && \
    cd /tmp && \
    wget https://github.com/xdebug/xdebug/archive/3.1.4.zip && \
    unzip 3.1.4.zip && \
    cd xdebug-3.1.4 && \
    phpize . && ./configure --with-php-config=`which php-config` && \
    make && make install && \
    docker-php-ext-install sockets && \
    wget https://github.com/DataDog/dd-trace-php/archive/0.74.0.tar.gz -O /tmp/datadog-php-tracer.tar.gz && \
    cd /tmp && tar -xvzf /tmp/datadog-php-tracer.tar.gz && cd dd-trace-php-0.74.0 && \
    phpize . && ./configure --with-php-config=`which php-config` && make && make install && \
    wget https://pecl.php.net/get/amqp-1.11.0.tgz -O /tmp/amqp-1.11.0.tgz && \
    cd /tmp && tar -xvzf /tmp/amqp-1.11.0.tgz && cd amqp-1.11.0 && \
    phpize . && ./configure --with-php-config=`which php-config` \
    && make && make install

ADD templates/extensions.ini.dist /usr/local/etc/php/conf.d/extensions.ini

VOLUME ["/var/www/revue-de-presse.org"]

WORKDIR /var/www/revue-de-presse.org

CMD ["tail", "-f", "/dev/null"]
