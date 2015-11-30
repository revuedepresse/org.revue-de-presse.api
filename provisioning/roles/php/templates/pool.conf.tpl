[global]

error_log = /var/log/php/php-fpm.error.log

[devobs]

access.log = /var/log/php/php-fpm-$pool.access.log

user = www-data
group = www-data

listen = /var/run/php5-fpm-devobs.sock
listen.allowed_clients = 127.0.0.1
listen.owner = www-data
listen.group = www-data

php_admin_value[error_log] = /var/log/php/php-fpm-$pool.error.log
php_admin_value[max_execution_time] = 10
php_admin_value[memory_limit] = 64M
php_admin_flag[log_errors] = on

pm = dynamic
pm.max_children = 5
pm.start_servers = 1
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500

catch_workers_output = yes

security.limit_extensions = .php .php3 .php4 .php5 .jpeg .jpg .gif .png .js .css
