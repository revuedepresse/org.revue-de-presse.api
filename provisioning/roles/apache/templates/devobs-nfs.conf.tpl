ServerName devobs-vagrant

<VirtualHost *:{{ apache.devobs_nfs.port }}>
    ServerAdmin webmaster@localhost
    DocumentRoot {{ apache.devobs_nfs.docroot }}
    ServerName {{ apache.devobs_nfs.servername }}

    <IfModule fastcgi_module>
        Alias /fcgi-bin/php5-fpm /fcgi-bin-php5-fpm-devobs-nfs
        FastCgiExternalServer /fcgi-bin-php5-fpm-devobs-nfs -socket /var/run/php5-fpm-devobs-nfs.sock -pass-header Authorization
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/error.devobs.log
    CustomLog ${APACHE_LOG_DIR}/access.devobs.log combined

    <Directory {{ apache.devobs_nfs.web }}>
        <IfModule mod_negotiation.c>
            Options -MultiViews
        </IfModule>

        DirectoryIndex app_dev.php

        AddCharset utf-8 .*
        AllowOverride None
        Options +FollowSymLinks -Indexes
        Require all granted

        <IfModule mod_rewrite.c>
            RewriteEngine on

            RewriteCond %{REQUEST_URI}::$1 ^(/.+)/(.*)::\2$
            RewriteRule ^(.*) - [E=BASE:%1]

            RewriteCond %{HTTP:Authorization} .
            RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

            RewriteCond %{REQUEST_FILENAME} -f
            RewriteRule .? - [L]

            RewriteRule .? %{ENV:BASE}/app_dev.php [L]
        </IfModule>
    </Directory>
</VirtualHost>
