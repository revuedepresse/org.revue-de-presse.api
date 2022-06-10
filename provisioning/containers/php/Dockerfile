FROM php:7.4.30-cli-bullseye

RUN apt-get update && \
    apt-get install -y \
    apt-utils \
    ca-certificates \
    wget unzip git libicu-dev libpq-dev librabbitmq-dev libcurl4-gnutls-dev && \
    docker-php-ext-install bcmath intl mysqli pcntl pdo_mysql pdo_pgsql && \
    docker-php-ext-install sockets && \
    wget https://github.com/DataDog/dd-trace-php/archive/0.74.0.tar.gz \
    --output-document=/tmp/datadog-php-tracer.tar.gz && \
    cd /tmp && \
    tar -xvzf /tmp/datadog-php-tracer.tar.gz && \
    cd dd-trace-php-0.74.0 && \
    phpize . && \
    ./configure --with-php-config="$(which php-config)" && \
    make && \
    make install && \
    wget https://github.com/DataDog/dd-trace-php/releases/latest/download/datadog-setup.php --output-document=/tmp/datadog-setup.php && \
    cd /tmp && \
    php datadog-setup.php \
    --php-bin all \
    --enable-appsec \
    --enable-profiling && \
    wget https://pecl.php.net/get/amqp-1.9.4.tgz -O /tmp/amqp-1.9.4.tgz && \
    cd /tmp && tar -xvzf /tmp/amqp-1.9.4.tgz && cd amqp-1.9.4 && \
    phpize . && ./configure --with-php-config=`which php-config` && make && make install

ADD templates/extensions.ini.dist /usr/local/etc/php/conf.d/extensions.ini

VOLUME /var/www/devobs

WORKDIR /var/www/devobs

CMD ["tail", "-f", "/dev/null"]
