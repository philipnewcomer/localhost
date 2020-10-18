<?php

namespace App;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class Config
{
    public function maybeCreateConfigDirectory()
    {
        File::ensureDirectoryExists(config('environment.config_directory_path'));
    }

    public function getUserConfig(string $key, $default)
    {
        $configFilePath = sprintf(
            '%s/%s.yml',
            config('environment.config_directory_path'),
            config('app.name')
        );

        $config = File::exists($configFilePath)
            ? (array) Yaml::parse(File::get($configFilePath))
            : [];

        return $config[$key] ?? $default;
    }
}
