# @see https://docs.docker.com/samples/library/php/ and also
# https://hub.docker.com/_/php?tab=tags for vulnerabilites
FROM php:7.4.4-fpm-buster

RUN apt-get update && apt-get install wget unzip libcurl4-openssl-dev -y && \
    docker-php-ext-install bcmath mysqli pcntl pdo_mysql && \
    cd /tmp && \
    wget https://github.com/xdebug/xdebug/archive/2.8.1.zip && \
    unzip 2.8.1.zip && \
    cd xdebug-2.8.1 && \
    phpize . && ./configure --with-php-config=`which php-config` && \
    make && make install && \
    docker-php-ext-install sockets && \
    pecl install apcu-5.1.19 && \
    docker-php-ext-enable apcu && \
    apt install -y gnupg2 && wget -q -O - https://packages.blackfire.io/gpg.key | apt-key add - && \
    echo "deb http://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list && \
    apt-get update && apt -y install blackfire-agent blackfire-agent blackfire-php && \
    wget https://github.com/DataDog/dd-trace-php/archive/0.29.0.tar.gz -O /tmp/datadog-php-tracer.tar.gz && \
    cd /tmp && tar -xvzf /tmp/datadog-php-tracer.tar.gz && cd dd-trace-php-0.29.0 && \
    phpize . && ./configure --with-php-config=`which php-config` && make && make install && \
    groupadd -g 1001 jenkins && useradd -s /bin/bash -u 1001 -g root -g jenkins -G www-data jenkins

ADD templates/20-xdebug.ini.dist /usr/local/etc/php/conf.d/20-xdebug.ini

CMD ["/etc/init.d/blackfire-agent restart && php-fpm"]

