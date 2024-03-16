<?php

namespace App;

use Illuminate\Support\Facades\File;

class Php
{
    /**
     * @var CommandLine
     */
    protected $commandLine;

    public function __construct(CommandLine $commandLine)
    {
        $this->commandLine = $commandLine;
    }

    public function link(string $version)
    {
        $command = sprintf(
            'brew unlink php && brew link php@%s --force --overwrite',
            $version
        );

        return $this->commandLine->run($command);
    }

    public function generateConfigs()
    {
        $replace = [
            'cafile' => sprintf('%s/cafile.pem', config('environment.config_directory_path')),
            'homebrewPrefix' => getenv('HOMEBREW_PREFIX'),
        ];

        $config = app(Stub::class)->get('php.ini', $replace);

        foreach (config('php.versions') as $phpVersion) {
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
            $replace = [
                'command' => config('app.command'),
                'user' => config('environment.user'),
                'listen' => sprintf(
                    '%s/php-%s.sock',
                    config('php.socket_directory'),
                    $phpVersion
                )
            ];

            $config = app(Stub::class)->get('php-fpm.conf', $replace);

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
