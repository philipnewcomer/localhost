<?php

namespace App;

use Illuminate\Support\Facades\File;

class Config
{
    public function maybeCreateConfigDirectory()
    {
        File::ensureDirectoryExists(config('environment.config_directory_path'));
    }
}
