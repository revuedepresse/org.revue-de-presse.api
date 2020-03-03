FROM php:7.4.2-apache-buster

RUN apt-get update && apt-get install -y wget git libicu-dev librabbitmq-dev libcurl4-gnutls-dev && \
    docker-php-ext-install bcmath intl mysqli pcntl pdo_mysql && \
    cd /tmp && \
    wget https://xdebug.org/files/xdebug-2.9.2.tgz && \
    tar -xvzf xdebug-2.9.2.tgz && \
    cd xdebug-2.9.2 && \
    phpize . && ./configure --with-php-config=`which php-config` && \
    make && make install && \
    docker-php-ext-install sockets && \
    wget https://github.com/DataDog/dd-trace-php/archive/0.29.0.tar.gz -O /tmp/datadog-php-tracer.tar.gz && \
    cd /tmp && tar -xvzf /tmp/datadog-php-tracer.tar.gz && cd dd-trace-php-0.29.0 && \
    phpize . && ./configure --with-php-config=`which php-config` && make && make install && \
    wget https://pecl.php.net/get/amqp-1.9.4.tgz -O /tmp/amqp-1.9.4.tgz && \
    cd /tmp && tar -xvzf /tmp/amqp-1.9.4.tgz && cd amqp-1.9.4 && \
    phpize . && ./configure --with-php-config=`which php-config` && make && make install && \
    apt install -y gnupg2 && wget -q -O - https://packages.blackfire.io/gpg.key | apt-key add - && \
    echo "deb http://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list && \
    apt-get update && apt install blackfire-agent blackfire-php

VOLUME /etc/apache2/sites-available

WORKDIR /var/www/devobs

ADD ./tasks /tasks

ADD templates/extensions.ini.dist /usr/local/etc/php/conf.d/extensions.ini

CMD /bin/bash -c "source /tasks/setup-virtual-host.sh && tail -f /var/www/devobs/var/logs/apache*.log"

