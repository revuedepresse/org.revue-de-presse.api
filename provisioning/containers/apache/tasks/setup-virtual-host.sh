#!/bin/bash

cd /etc/apache2

ln -s /templates/sites-enabled/press-review.conf /etc/apache2/sites-available

# Disable default virtual host
a2dissite 000-default.conf

# Enable rewrite module
a2enmod rewrite

# Enable headers module for CORS
a2enmod headers

cd /etc/apache2
ln -s `pwd`/sites-available/press-review.conf `pwd`/sites-enabled

apache2-foreground &
