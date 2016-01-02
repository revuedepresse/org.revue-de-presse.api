# Development

## Debugging

To debug a command of the application by running tests with `PHPUnit` and a custom autoloader using `PHPStorm`

 - Ensure the targeted test is not run in isolation (by altering `PHPUnit` configuration in `app` directory)
 - `XDebug` configuration has been updated via provisioning in the vagrant box according to the template located at
 `/provisioning/roles/php/templates/xdebug.ini.tpl`
   - `xdebug.remote_host` should match the Vagrant host IP address
   - `xdebug.idekey` should match the configured IDE key
   - `xdebug.remote_enable` should be set to `1`

By disabling process isolation, PHPStorm will not hang while waiting for incoming connection.

Run tests using `PHPUnit` and a custom autoloader

```
cd /var/deploy/devobs/current && \
XDEBUG_CONFIG='idekey=phpstorm-xdebug' vendor/bin/phpunit -c app/phpunit.xml  \
--stop-on-error \
--stop-on-failure \
--stop-on-incomplete \
--stop-on-risky
```

## FAQ

**How can I restart services in the Vagrant box?**

Restart Apache

```
sudo service apache2 restart
```

Restart PHP FPM

```
sudo service php5-fpm restart
```

Restart mysql

```
sudo service mysql restart
```

Restart Varnish

```
sudo service varnish restart
```

Restart RabbitMQ

```
sudo service rabbitmq-server restart
```

Restart Elasticsearch

```
sudo service elasticsearch restart
```

Restart Redis

```
sudo service redis_6379 restart
```
