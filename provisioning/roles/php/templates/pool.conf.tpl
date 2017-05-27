[global]

error_log = /var/log/php/php-fpm.error.log

[devobs]

env[SYMFONY__AES__KEY]=_
env[SYMFONY__AES__IV]=_
env[SYMFONY__ANALYTICS__HOST]=_
env[SYMFONY__ANALYTICS__SITE_ID]=_
env[SYMFONY__ASSETIC__NODE]=/home/vagrant/.nvm/versions/node/v4.2.2/bin/node
env[SYMFONY__ASSETIC__MODULES]=/home/vagrant/.nvm/versions/node/v4.2.2/lib/node_modules
env[SYMFONY__APC__HOST]=http://127.0.0.1
env[SYMFONY__API__FACEBOOK__APP_ID]=_
env[SYMFONY__API__FACEBOOK__APP_SECRET]=_
env[SYMFONY__API__FACEBOOK__PROTOCOL]=http://
env[SYMFONY__API__FACEBOOK__HOST_PORT]=127.0.0.1
env[SYMFONY__API__TWITTER__HOST_PORT]=start.weaving-the-web.org
env[SYMFONY__API__TWITTER__PROTOCOL]=https://
env[SYMFONY__API__TWITTER__CONSUMER_KEY]='_'
env[SYMFONY__API__TWITTER__CONSUMER_SECRET]='_'
env[SYMFONY__API__TWITTER__CALLBACK_URL]='http://10.9.8.2/twitter/login_connect'
env[SYMFONY__API__TWITTER__USER_TOKEN]=_
env[SYMFONY__API__TWITTER__USER_SECRET]=_
env[SYMFONY__API__TWITTER__VERSION]=1.1
env[SYMFONY__ELASTICSEARCH__HOST]=127.0.0.1
env[SYMFONY__ELASTICSEARCH__PORT]=9200
env[SYMFONY__FRAMEWORK__SECRET]='~p^o4O?/vil)tL^oqJkE+DHB~?OE/Or%%J"=|P7fm'
env[SYMFONY__IMAP__USERNAME]=_
env[SYMFONY__IMAP__PASSWORD]=_
env[SYMFONY__MYSQL__DATABASE]=devobs_dev
env[SYMFONY__MYSQL__USER]=default
env[SYMFONY__MYSQL__PASSWORD]='r>$$#F)D=*NchQVz*@.x'
env[SYMFONY__MYSQL__HOST]=127.0.0.1
env[SYMFONY__MYSQL__PORT]=3306
env[SYMFONY__MYSQL__DATABASE_READ]=devobs_dev
env[SYMFONY__MYSQL__USER_READ]=default
env[SYMFONY__MYSQL__PASSWORD_READ]='r>$$#F)D=*NchQVz*@.x'
env[SYMFONY__MYSQL__HOST_READ]=127.0.0.1
env[SYMFONY__MYSQL__PORT_READ]=3306
env[SYMFONY__MYSQL__DATABASE_WRITE]=devobs_dev
env[SYMFONY__MYSQL__USER_WRITE]=default
env[SYMFONY__MYSQL__PASSWORD_WRITE]='r>$$#F)D=*NchQVz*@.x'
env[SYMFONY__MYSQL__HOST_WRITE]=127.0.0.1
env[SYMFONY__MYSQL__PORT_WRITE]=3306
env[SYMFONY__MYSQL__TEST_DATABASE]=devobs_test
env[SYMFONY__MYSQL__TEST_USER]=tester
env[SYMFONY__MYSQL__TEST_PASSWORD]=ly&r{tKh-65Zvi+9S%d
env[SYMFONY__MYSQL__ADMIN_USER]=root
env[SYMFONY__MYSQL__ADMIN_PASSWORD]='ees)~JIGP0iB)3jD9<'
env[SYMFONY__OAUTH__CLIENT_ID]=_
env[SYMFONY__OAUTH__CLIENT_SECRET]=_
env[SYMFONY__OAUTH__ACCESS_TOKEN]=_
env[SYMFONY__QUALITY_ASSURANCE__PASSWORD]='gid}\hx#wcJ9}@%An+=F5]Ou>^$Bur'
env[SYMFONY__RABBITMQ__USER]=default
env[SYMFONY__RABBITMQ__PASSWORD]='eyLDwgkwQnoN'
env[SYMFONY__RABBITMQ__HOST]=127.0.0.1
env[SYMFONY__RABBITMQ__VHOST]=/

access.log = /var/log/php/php-fpm-$pool.access.log

user = www-data
group = www-data

listen = /var/run/php5-fpm-devobs.sock
listen.allowed_clients = 127.0.0.1
listen.owner = www-data
listen.group = www-data

php_admin_value[error_log] = /var/log/php/php-fpm-$pool.error.log
php_admin_value[max_execution_time] = 10
php_admin_value[memory_limit] = 256M
php_admin_flag[log_errors] = on

pm = dynamic
pm.max_children = 5
pm.start_servers = 1
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500

catch_workers_output = yes

security.limit_extensions = .php .php3 .php4 .php5 .jpeg .jpg .gif .png .js .css

[devobs-nfs]

env[SYMFONY__AES__KEY]=_
env[SYMFONY__AES__IV]=_
env[SYMFONY__ANALYTICS__HOST]=_
env[SYMFONY__ANALYTICS__SITE_ID]=_
env[SYMFONY__ASSETIC__NODE]=/home/vagrant/.nvm/versions/node/v4.2.2/bin/node
env[SYMFONY__ASSETIC__MODULES]=/home/vagrant/.nvm/versions/node/v4.2.2/lib/node_modules
env[SYMFONY__APC__HOST]=http://127.0.0.1
env[SYMFONY__API__FACEBOOK__APP_ID]=_
env[SYMFONY__API__FACEBOOK__APP_SECRET]=_
env[SYMFONY__API__FACEBOOK__PROTOCOL]=http://
env[SYMFONY__API__FACEBOOK__HOST_PORT]=127.0.0.1
env[SYMFONY__API__TWITTER__HOST_PORT]=start.weaving-the-web.org
env[SYMFONY__API__TWITTER__PROTOCOL]=https://
env[SYMFONY__API__TWITTER__CONSUMER_KEY]='_'
env[SYMFONY__API__TWITTER__CONSUMER_SECRET]='_'
env[SYMFONY__API__TWITTER__CALLBACK_URL]='http://10.9.8.2/twitter/login_connect'
env[SYMFONY__API__TWITTER__USER_TOKEN]=_
env[SYMFONY__API__TWITTER__USER_SECRET]=_
env[SYMFONY__API__TWITTER__VERSION]=1.1
env[SYMFONY__ELASTICSEARCH__HOST]=127.0.0.1
env[SYMFONY__ELASTICSEARCH__PORT]=9200
env[SYMFONY__FRAMEWORK__SECRET]='~p^o4O?/vil)tL^oqJkE+DHB~?OE/Or%%J"=|P7fm'
env[SYMFONY__IMAP__USERNAME]=_
env[SYMFONY__IMAP__PASSWORD]=_
env[SYMFONY__MYSQL__DATABASE]=devobs_dev
env[SYMFONY__MYSQL__USER]=default
env[SYMFONY__MYSQL__PASSWORD]='r>$$#F)D=*NchQVz*@.x'
env[SYMFONY__MYSQL__HOST]=127.0.0.1
env[SYMFONY__MYSQL__PORT]=3306
env[SYMFONY__MYSQL__DATABASE_READ]=devobs_dev
env[SYMFONY__MYSQL__USER_READ]=default
env[SYMFONY__MYSQL__PASSWORD_READ]='r>$$#F)D=*NchQVz*@.x'
env[SYMFONY__MYSQL__HOST_READ]=127.0.0.1
env[SYMFONY__MYSQL__PORT_READ]=3306
env[SYMFONY__MYSQL__DATABASE_WRITE]=devobs_dev
env[SYMFONY__MYSQL__USER_WRITE]=default
env[SYMFONY__MYSQL__PASSWORD_WRITE]='r>$$#F)D=*NchQVz*@.x'
env[SYMFONY__MYSQL__HOST_WRITE]=127.0.0.1
env[SYMFONY__MYSQL__PORT_WRITE]=3306
env[SYMFONY__MYSQL__TEST_DATABASE]=devobs_test
env[SYMFONY__MYSQL__TEST_USER]=tester
env[SYMFONY__MYSQL__TEST_PASSWORD]=ly&r{tKh-65Zvi+9S%d
env[SYMFONY__MYSQL__ADMIN_USER]=root
env[SYMFONY__MYSQL__ADMIN_PASSWORD]='ees)~JIGP0iB)3jD9<'
env[SYMFONY__OAUTH__CLIENT_ID]=_
env[SYMFONY__OAUTH__CLIENT_SECRET]=_
env[SYMFONY__OAUTH__ACCESS_TOKEN]=_
env[SYMFONY__QUALITY_ASSURANCE__PASSWORD]='gid}\hx#wcJ9}@%An+=F5]Ou>^$Bur'
env[SYMFONY__RABBITMQ__USER]=default
env[SYMFONY__RABBITMQ__PASSWORD]='eyLDwgkwQnoN'
env[SYMFONY__RABBITMQ__HOST]=127.0.0.1
env[SYMFONY__RABBITMQ__VHOST]=/

access.log = /var/log/php/php-fpm-$pool.access.log

user = www-data
group = www-data

listen = /var/run/php5-fpm-devobs-nfs.sock
listen.allowed_clients = 127.0.0.1
listen.owner = www-data
listen.group = www-data

php_admin_value[error_log] = /var/log/php/php-fpm-$pool.error.log
php_admin_value[max_execution_time] = 10
php_admin_value[memory_limit] = 256M
php_admin_flag[log_errors] = on

pm = dynamic
pm.max_children = 5
pm.start_servers = 1
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500

catch_workers_output = yes

security.limit_extensions = .php .php3 .php4 .php5 .jpeg .jpg .gif .png .js .css
