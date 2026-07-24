<?php

namespace App;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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

    /**
     * @return string[]
     */
    public function getVersions(): array
    {
        $versions = collect();

        foreach (File::directories(config('php.configs_directory')) as $directory) {
            $version = Str::afterLast($directory, '/');
            if (is_numeric($version)) {
                $versions[] = $version;
            }
        }

        return $versions->sortDesc()->values()->toArray();
    }

    public function link(string $version)
    {

        if ('macos' === config('environment.platform')) {
            $this->commandLine->run(sprintf(
                'brew unlink php && brew link php@%s --force --overwrite',
                $version
            ));
        } else {
            $this->commandLine->run(sprintf(
                'sudo update-alternatives --set php /usr/bin/php%s',
                $version
            ));
        }
    }

    public function generateConfigs()
    {
        foreach ($this->getVersions() as $phpVersion) {
            $path = sprintf(
                '%s/%s/%s/%s.ini',
                config('php.configs_directory'),
                $phpVersion,
                config('environment.platform') === 'macos' ? 'conf.d' : 'mods-available',
                config('app.command')
            );

            app(Stub::class)->put('php.ini', $path);
        }

        if ('linux' === config('environment.platform')) {
            $this->commandLine->run(sprintf('sudo phpenmod %s', config('app.command')));
        }
    }

    public function cleanDefaultIniFileIncludes(): void
    {
        foreach ($this->getVersions() as $phpVersion) {
            $path = sprintf('%s/%s/php.ini', config('php.configs_directory'), $phpVersion);

            $fileContent = File::get($filePath);

            $newFileContent = str_replace(
                'zend_extension="xdebug.so"' . "\n",
                '',
                $fileContent
            );

            File::put($filePath, $newFileContent);

            $this->commandLine->run(sprintf(
                'sudo -- sh -c "echo %s > %s"',
                escapeshellarg(str_replace('"', '\"', $contents)),
                $path
            ));
        }
    }

    public function deleteConfigs()
    {
        if ('linux' === config('environment.platform')) {
            $this->commandLine->run(sprintf('sudo phpdismod %s', config('app.command')));
        }

        foreach ($this->getVersions() as $phpVersion) {
            $path = sprintf(
                '%s/%s/%s/%s.ini',
                config('php.configs_directory'),
                $phpVersion,
                config('environment.platform') === 'macos' ? 'conf.d' : 'mods-available',
                config('app.command')
            );

            if (File::exists($path)) {
                app(Filesystem::class)->delete($path);
            }
        }
    }

    public function getSocketPath(string $phpVersion): string
    {
        if ('macos' === config('environment.platform')) {
            return sprintf(
                '%s/php-%s.sock',
                config('environment.user_home_directory_path'),
                $phpVersion
            );
        } else {
            return sprintf('/run/php/php%s-fpm.sock', $phpVersion);
        }
    }

    public function generateFpmConfigs()
    {
        foreach ($this->getVersions() as $phpVersion) {
            $replace = [
                'command' => config('app.command'),
                'user' => config('environment.user'),
                'group' => config('environment.group'),
                'listen' => $this->getSocketPath($phpVersion),
            ];

            $path = sprintf(
                '%s/%s/%s/www%s-overrides.conf',
                config('php.configs_directory'),
                $phpVersion,
                'macos' === config('environment.platform') ? 'php-fpm.d' : 'fpm/pool.d',
                config('app.command')
            );

            app(Stub::class)->put('php-fpm.conf', $path, $replace);
        }
    }

    public function deleteFpmConfigs()
    {
        foreach ($this->getVersions() as $phpVersion) {
            $filePath = sprintf(
                '%s/%s/%s/www%s-overrides.conf',
                config('php.configs_directory'),
                $phpVersion,
                'macos' === config('environment.platform') ? 'php-fpm.d' : 'fpm/pool.d',
                config('app.command')
            );

            if (File::exists($filePath)) {
                app(Filesystem::class)->delete($filePath);
            }
        }
    }
}
