FROM php:7.2.11-zts-stretch

RUN apt-get update && apt-get install wget unzip git -y && \
    docker-php-ext-install mysqli pdo_mysql bcmath pcntl && \
    cd /tmp && git clone https://github.com/krakjoe/pthreads && \
    cd pthreads && \
    phpize . && ./configure --with-php-config=`which php-config` --enable-pthreads && \
    make && make install && \
    cd /tmp && wget https://github.com/xdebug/xdebug/archive/2.6.0.zip && \
    unzip 2.6.0.zip && \
    cd xdebug-2.6.0 && \
    phpize . && ./configure --with-php-config=`which php-config` && make && make install && \
    apt install -y gnupg2 && wget -q -O - https://packagecloud.io/gpg.key | apt-key add - && \
    echo "deb http://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list && \
    apt-get update && apt install blackfire-agent blackfire-agent blackfire-php

ADD templates/20-xdebug.ini.dist /usr/local/etc/php/conf.d/20-xdebug.ini
ADD templates/extensions.ini     /usr/local/etc/php/conf.d/extensions.ini

CMD ["php", "-a"]
