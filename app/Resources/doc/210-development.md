# Development

## Shared folders

Exporting the `USE_NFS` environment variable with value `true`
will enable the use of NFS to share the project folder between the Vagrant
host and guest machines on condition that a NFS server has been
installed on the host machine first.

```
export USE_NFS=true && vagrant up # or
USE_NFS=true vagrant up
```

For instance, in Ubuntu Linux, installing NFS requirements can be
achieved by executing the following command:

```
apt-get install nfs-kernel-server
```

Whenever a NFS server can not be installed, the project directory is not
shared between Vagrant host and guest machines. However a fallback
mechanism exists to let the project directory be shared using `rsync`.  
Exporting the `USE_RSYNC` environment variable with value `true` will
enable the use of rsync to share the project folder.

```
export USE_RSYNC=true && vagrant up # or
USE_RSYNC=true vagrant up
```

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
