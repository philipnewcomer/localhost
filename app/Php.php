<?php

namespace App;

use Illuminate\Support\Facades\File;

class Php
{
    /**
     * @var CommandLine
     */
    protected $commandLine;

    /**
     * @var Config
     */
    protected $config;

    public function __construct(CommandLine $commandLine, Config $config)
    {
        $this->commandLine = $commandLine;
        $this->config = $config;
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
        $xdebugEnabled = $this->config->getUserConfig('xdebug_enabled', true);

        $replace = [
            'homebrewPrefix' => getenv('HOMEBREW_PREFIX'),
            'xdebugZendExtension' => $xdebugEnabled ? 'zend_extension' : '#zend_extension'
        ];

        $config = app(Stub::class)->get('php.ini', $replace);

        foreach (config('php.versions') as $phpVersion) {
            $filePath = sprintf(
                '%s/conf.d/%s.ini',
                str_replace('{version}', $phpVersion, config('php.config_directory')),
                config('app.command')
            );

            File::put($filePath, $config);
        }
    }

    public function cleanDefaultIniFileIncludes(): void
    {
        foreach (config('php.versions') as $phpVersion) {
            $filePath = str_replace(
                '{version}',
                $phpVersion,
                config('php.config_directory')
            ) . '/php.ini';

            $fileContent = File::get($filePath);

            $newFileContent = str_replace(
                'zend_extension="xdebug.so"' . "\n",
                '',
                $fileContent
            );

            File::put($filePath, $newFileContent);
        }
    }

    public function deleteConfigs()
    {
        foreach (config('php.versions') as $phpVersion) {
            $filePath = sprintf(
                '%s/conf.d/%s.ini',
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
                '%s/php-fpm.d/%s.conf',
                str_replace('{version}', $phpVersion, config('php.config_directory')),
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
                '%s/php-fpm.d/www.conf',
                str_replace('{version}', $phpVersion, config('php.config_directory'))
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
                '%s/php-fpm.d/%s.conf',
                str_replace('{version}', $phpVersion, config('php.config_directory')),
                config('app.command')
            );

            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
    }
}
