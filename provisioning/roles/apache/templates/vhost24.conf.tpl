ServerName devobs-vagrant

<VirtualHost *:{{ apache.port }}>
    ServerAdmin webmaster@localhost
    DocumentRoot {{ apache.docroot }}
    ServerName {{ apache.servername }}

    <IfModule fastcgi_module>
        Alias /fcgi-bin/php5-fpm /fcgi-bin-php5-fpm-devobs
        FastCgiExternalServer /fcgi-bin-php5-fpm-devobs -socket /var/run/php5-fpm-devobs.sock -pass-header Authorization
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/error.devobs.log
    CustomLog ${APACHE_LOG_DIR}/access.devobs.log combined

    <Directory {{ apache.releases }}>
        <IfModule mod_negotiation.c>
            Options -MultiViews
        </IfModule>
       
        AddCharset utf-8 .*
        AllowOverride None
        Options +FollowSymLinks -Indexes
        Require all granted

        <IfModule mod_rewrite.c>
            RewriteEngine on
            RewriteCond %{REQUEST_URI}::$1 ^(/.+)/(.*)::\2$
            RewriteRule ^(.*) - [E=BASE:%1]

            RewriteCond %{ENV:REDIRECT_STATUS} ^$
            RewriteRule ^app\.php(/(.*)|$) %{ENV:BASE}/$2 [R=301,L]

            RewriteCond %{REQUEST_FILENAME} -f
            RewriteRule .? - [L]

            RewriteRule .? %{ENV:BASE}/app.php [L]
        </IfModule>
    </Directory>
</VirtualHost>
