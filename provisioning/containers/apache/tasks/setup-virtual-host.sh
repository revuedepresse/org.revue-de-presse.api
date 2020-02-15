#!/bin/bash

function init_virtual_host() {
    cd /etc/apache2

    if [ ! -e /etc/apache2/sites-available ];
    then
        ln -s /templates/sites-enabled/press-review.conf /etc/apache2/sites-available
    fi

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

    /etc/init.d/blackfire-agent restart

    apache2-foreground &
}
init_virtual_host