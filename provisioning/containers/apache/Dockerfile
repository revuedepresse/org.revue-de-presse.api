FROM php:7.2.6-apache-stretch

RUN apt-get update && apt-get install wget unzip && \
    docker-php-ext-install mysqli pdo_mysql bcmath pcntl && \
    cd /tmp && \
    wget https://github.com/xdebug/xdebug/archive/2.6.0.zip && \
    unzip 2.6.0.zip && \
    cd xdebug-2.6.0 && \
    phpize . && ./configure --with-php-config=`which php-config` && make && make install

ADD templates/20-xdebug.ini.dist /usr/local/etc/php/conf.d/20-xdebug.ini

VOLUME /etc/apache2/sites-available

WORKDIR /etc/apache2/sites-available

CMD ["apache2-foreground"]

