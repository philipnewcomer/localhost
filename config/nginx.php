<?php

return [
    'sites_config_directory' => ('macos' === config('environment.platform'))
        ? getenv('HOMEBREW_PREFIX') . '/etc/nginx/servers'
        : '/etc/nginx/conf.d'
];
