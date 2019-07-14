FROM php:7.2.11-cli-stretch

RUN apt-get update && apt-get install wget unzip && \
    docker-php-ext-install bcmath mysqli pcntl pdo_mysql && \
    cd /tmp && \
    wget https://github.com/xdebug/xdebug/archive/2.6.0.zip && \
    unzip 2.6.0.zip && \
    cd xdebug-2.6.0 && \
    phpize . && ./configure --with-php-config=`which php-config` && \
    make && make install && \
    docker-php-ext-install sockets && \
    apt install -y gnupg2 && wget -q -O - https://packagecloud.io/gpg.key | apt-key add -

ADD templates/20-xdebug.ini.dist /usr/local/etc/php/conf.d/20-xdebug.ini

CMD ["php", "-a"]
