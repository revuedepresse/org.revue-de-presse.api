<?php

$container->loadFromExtension(
    'doctrine',
    array(
        'dbal' => array(
            'driver'       => '%database_driver%',
            'host'         => '%database_host%',
            'port'         => '%database_port%',
            'dbname'       => '%database_name%',
            'charset'      => '%database_charset%',
            'user'         => '%database_user%',
            'password'     => '%database_password%'
        )
    )
);