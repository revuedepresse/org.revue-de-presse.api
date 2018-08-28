#!/bin/bash

cd /etc/apache2

ln -s /templates/sites-enabled/press-review.conf /etc/apache2/sites-available

# Disable default virtual host
a2dissite 000-default.conf

# Enable rewrite module
a2enmod rewrite

# Enable headers module for CORS
a2enmod headers

# Enable HTTP2
a2enmod http2

cd /etc/apache2
ln -s `pwd`/sites-available/press-review.conf `pwd`/sites-enabled

rm `pwd`/mods-enabled/deflate.conf
ln -s /templates/mods-enabled/deflate.conf `pwd`/mods-enabled

apache2-foreground &
