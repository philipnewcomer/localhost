<?php

return [
    'user' => get_current_user(),
    'group' => PHP_OS === 'Darwin' ? 'staff' : get_current_user(),
    'user_home_directory_path' => getenv('HOME'),
    'platform' => PHP_OS === 'Darwin' ? 'macos' : 'linux',
    'config_directory_path' => sprintf('%s/.%s', getenv('HOME'), config('app.command'))
];
