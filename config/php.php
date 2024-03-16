<?php

return [
    'config_directory' => getenv('HOMEBREW_PREFIX') . '/etc/php/{version}',
    'default_version' => '8.2',
    'socket_directory' => sprintf(
        '%s/.%s',
        config('environment.user_home_directory_path'),
        config('app.command')
    ),
    'versions' => [
        '8.1',
        '8.2'
    ],
    'openssl_cert_path' => '/usr/local/etc/openssl@1.1/cert.pem'
];
