<?php

return [
    'user' => get_current_user(),
    'user_home_directory_path' => getenv('HOME'),
    'config_directory_path' => sprintf('%s/.%s', getenv('HOME'), config('app.command'))
];
