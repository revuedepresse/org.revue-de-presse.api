# Configure all that stuff needed for using PHP-FPM as FastCGI

# Set handlers for PHP files.
# application/x-httpd-php                        phtml pht php
# application/x-httpd-php3                       php3
# application/x-httpd-php4                       php4
# application/x-httpd-php5                       php
<FilesMatch ".+\.ph(p[345]?|t|tml)$">
    SetHandler application/x-httpd-php
</FilesMatch>

# application/x-httpd-php-source                 phps
<FilesMatch ".+\.phps$">
    SetHandler application/x-httpd-php-source
    # Deny access to raw php sources by default
    # To re-enable it's recommended to enable access to the files
    # only in specific virtual host or directory
    Require all denied
</FilesMatch>

# Deny access to files without filename (e.g. '.php')
<FilesMatch "^\.ph(p[345]?|t|tml|ps)$">
    Require all denied
</FilesMatch>

# Define Action and Alias needed for FastCGI external server.
Action application/x-httpd-php /fcgi-bin/php5-fpm virtual
Alias /fcgi-bin/php5-fpm /fcgi-bin-php5-fpm

<Location /fcgi-bin/php5-fpm>
    # here we prevent direct access to this Location url,
    # env=REDIRECT_STATUS will let us use this fcgi-bin url
    # only after an internal redirect (by Action upper)
    Require env REDIRECT_STATUS
</Location>
