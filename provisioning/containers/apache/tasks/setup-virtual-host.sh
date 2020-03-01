#!/bin/bash

function init_virtual_host() {
    cd /etc/apache2 || exit

    if [ ! -e /etc/apache2/sites-available/press-review.conf ];
    then
        ln -s /templates/sites-enabled/press-review.conf /etc/apache2/sites-available
    fi

    # Disable default virtual host
    a2dissite 000-default.conf

    # Enable rewrite module
    a2enmod rewrite

    # Enable headers module for CORS
    a2enmod headers

    cd /etc/apache2 || exit

    local working_directory
    working_directory="$(pwd)"

    if [ -L "${working_directory}"/sites-enabled/press-review.conf ];
    then
        rm "${working_directory}"/sites-enabled/press-review.conf
    fi

    ln -s "${working_directory}"/sites-available/press-review.conf "${working_directory}"/sites-enabled

    if [ -L "${working_directory}"/mods-enabled/deflate.conf ];
    then
        rm "${working_directory}"/mods-enabled/deflate.conf
    fi

    ln -s /templates/mods-enabled/deflate.conf "${working_directory}"/mods-enabled

    /etc/init.d/blackfire-agent restart

    apache2-foreground &
}
init_virtual_host