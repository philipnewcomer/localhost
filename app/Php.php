<?php

namespace App;

use Illuminate\Support\Facades\File;

class Php
{
    public function generateConfigs()
    {
        foreach (config('php.versions') as $phpVersion) {
            $config = File::get('stubs/php.ini');

            $filePath = sprintf(
                '%s/%s.ini',
                str_replace('{version}', $phpVersion, config('php.config_directory')),
                config('app.command')
            );

            File::put($filePath, $config);
        }
    }

    public function deleteConfigs()
    {
        foreach (config('php.versions') as $phpVersion) {
            $filePath = sprintf(
                '%s/%s.ini',
                str_replace('{version}', $phpVersion, config('php.config_directory')),
                config('app.command')
            );

            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
    }

    public function generateFpmConfigs()
    {
        foreach (config('php.versions') as $phpVersion) {
            $config = File::get('stubs/php-fpm.conf');

            $replace = [
                'command' => config('app.command'),
                'user' => config('environment.user'),
                'listen' => sprintf(
                    '%s/php-%s.sock',
                    config('php.socket_directory'),
                    $phpVersion
                )
            ];

            foreach ($replace as $placeholder => $value) {
                $config = str_replace(
                    sprintf('{%s}', $placeholder),
                    $value,
                    $config
                );
            }

            $filePath = sprintf(
                '%s/%s.conf',
                str_replace('{version}', $phpVersion, config('php.fpm_config_directory')),
                config('app.command')
            );

            File::put($filePath, $config);
        }

        $this->maybeDisableDefaultFpmConfigs();
    }

    public function maybeDisableDefaultFpmConfigs()
    {
        // @todo figure the situation with a clean install of fpm.

        foreach (config('php.versions') as $phpVersion) {
            $defaultConfigFilePath = sprintf(
                '%s/www.conf',
                str_replace('{version}', $phpVersion, config('php.fpm_config_directory'))
            );

            if (File::exists($defaultConfigFilePath)) {
                File::move($defaultConfigFilePath, $defaultConfigFilePath . '.bak');
            }
        }
    }

    public function deleteFpmConfigs()
    {
        foreach (config('php.versions') as $phpVersion) {
            $filePath = sprintf(
                '%s/%s.conf',
                str_replace('{version}', $phpVersion, config('php.fpm_config_directory')),
                config('app.command')
            );

            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
    }
}
