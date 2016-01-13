[client]
port		= 3306
socket		= /var/run/mysqld/mysqld.sock
password    = '{{ mysql.root_password }}'

[mysqld_safe]
socket		= /var/run/mysqld/mysqld.sock
nice		= 0

[mysqld]

user		= mysql
pid-file	= /var/run/mysqld/mysqld.pid
socket		= /var/run/mysqld/mysqld.sock
port		= 3306
basedir		= /usr
datadir		= /var/lib/mysql
tmpdir		= /tmp
lc-messages-dir	= /usr/share/mysql
skip-external-locking
bind-address	= 127.0.0.1
key_buffer		= 16M
group_concat_max_len=1048576
max_allowed_packet	= 16M
thread_stack		= 192K
thread_cache_size   = 8
myisam-recover      = BACKUP
query_cache_limit	= 1M
query_cache_size    = 16M
log_error = /var/log/mysql/error.log
expire_logs_days	= 10
max_binlog_size     = 100M
server-id           = 4
log-bin             = mysql-bin
relay-log           = mysql-relay-bin

[mysqldump]
quick
quote-names
max_allowed_packet	= 16M

[mysql]

[isamchk]
key_buffer		= 16M

!includedir /etc/mysql/conf.d/
