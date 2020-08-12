<?php

return [
    'default_version' => '7.4',
    'socket_directory' => sprintf(
        '%s/.%s',
        config('environment.user_home_directory_path'),
        config('app.command')
    ),
    'versions' => [
        '7.2',
        '7.3',
        '7.4'
    ],
    'fpm_config_directory' => '/usr/local/etc/php/{version}/php-fpm.d'
];
