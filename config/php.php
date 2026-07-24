<?php

return [
    'configs_directory' => ('macos' === config('environment.platform') ? getenv('HOMEBREW_PREFIX') : '') . '/etc/php',
];
