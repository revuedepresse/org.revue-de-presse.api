FROM php:7.2.6-cli-stretch

RUN apt-get update && apt-get install wget unzip && \
    docker-php-ext-install mysqli pdo_mysql bcmath pcntl && \
    cd /tmp && \
    wget https://github.com/xdebug/xdebug/archive/2.6.0.zip && \
    unzip 2.6.0.zip && \
    cd xdebug-2.6.0 && \
    phpize . && ./configure --with-php-config=`which php-config` && make && make install && \
    apt install -y gnupg2 && wget -q -O - https://packagecloud.io/gpg.key | apt-key add - && \
    echo "deb http://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list && \
    apt-get update && apt install blackfire-agent blackfire-agent blackfire-php

RUN useradd -s /bin/bash -u 1001 -g root -G www-data jenkins

ADD templates/20-xdebug.ini.dist /usr/local/etc/php/conf.d/20-xdebug.ini

CMD ["php", "-a"]
