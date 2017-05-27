<VirtualHost *:{{ apache.devobs.port }}>
    ServerAdmin webmaster@localhost
    DocumentRoot {{ apache.devobs.docroot }}
    ServerName {{ apache.devobs.servername }}

    <IfModule fastcgi_module>
        Alias /fcgi-bin/php5-fpm /fcgi-bin-php5-fpm-devobs
        FastCgiExternalServer /fcgi-bin-php5-fpm-devobs -socket /var/run/php5-fpm-devobs.sock -pass-header Authorization
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/error.devobs.log
    CustomLog ${APACHE_LOG_DIR}/access.devobs.log combined

    <Directory {{ apache.devobs.releases }}>
        <IfModule mod_negotiation.c>
            Options -MultiViews
        </IfModule>

        DirectoryIndex app.php

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

            RewriteRule .? %{ENV:BASE}/app.php [L]
        </IfModule>
    </Directory>
</VirtualHost>
