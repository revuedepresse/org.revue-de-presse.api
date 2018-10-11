FROM php:7.2.6-apache-stretch

RUN apt-get update && apt-get install wget unzip && \
    docker-php-ext-install mysqli pdo_mysql bcmath pcntl && \
    cd /tmp && \
    wget https://github.com/xdebug/xdebug/archive/2.6.0.zip && \
    unzip 2.6.0.zip && \
    cd xdebug-2.6.0 && \
    phpize . && ./configure --with-php-config=`which php-config` && \
    make && make install && \
    pecl install apcu-5.1.12 && \
    docker-php-ext-enable apcu && \
    apt install -y gnupg2 && wget -q -O - https://packagecloud.io/gpg.key | apt-key add - && \
    echo "deb http://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list && \
    apt-get update && apt install blackfire-agent blackfire-agent blackfire-php

VOLUME /etc/apache2/sites-available

WORKDIR /var/www/devobs

CMD ["/etc/init.d/blackfire-agent restart && apache2-foreground"]

