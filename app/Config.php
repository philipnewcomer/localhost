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

    public function getUserConfig(string $key, $default = null)
    {
        $currentConfig = $this->getCurrentConfig();

        return $currentConfig[$key] ?? $default;
    }

    public function setUserConfig(string $key, $value)
    {
        $configFilePath = sprintf(
            '%s/%s.yml',
            config('environment.config_directory_path'),
            config('app.name')
        );

        $currentConfig = $this->getCurrentConfig();

        $currentConfig[$key] = $value;

        File::put($configFilePath, Yaml::dump($currentConfig));
    }

    protected function getCurrentConfig(): array
    {
        $configFilePath = sprintf(
            '%s/%s.yml',
            config('environment.config_directory_path'),
            config('app.name')
        );

        if (!File::exists($configFilePath)) {
            return [];
        }

        return (array) Yaml::parse(File::get($configFilePath));
    }
}
