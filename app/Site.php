<?php

namespace App;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class Site
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $settings = [];

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->readSettings();
    }

    protected function readSettings()
    {
        $settingsFilePath = sprintf(
            '%s/%s.yml',
            $this->path,
            config('app.command')
        );

        if (File::exists($settingsFilePath)) {
            $this->settings = (array) Yaml::parse(File::get($settingsFilePath));
        }
    }

    public function getPublicDirectoryPath()
    {
        $publicDirectory = $this->path;

        if (File::exists($publicDirectory . '/htdocs') && File::isDirectory($publicDirectory . '/htdocs')) {
            $publicDirectory .= '/htdocs';
        }

        if (File::exists($publicDirectory . '/public') && File::isDirectory($publicDirectory . '/public')) {
            $publicDirectory .= '/public';
        }

        return $publicDirectory;
    }

    public function getHosts()
    {
        $hosts = [];

        if (empty($this->settings['host']) && empty($this->settings['hosts'])) {
            $hosts[] = sprintf(
                '%s.%s',
                Str::afterLast($this->path, '/'),
                config('sites.default_tld')
            );
        }

        if (! empty($this->settings['host'])) {
            $hosts[] = $this->settings['host'];
        }

        if (! empty($this->settings['hosts'])) {
            $hosts = array_merge($hosts, $this->settings['hosts']);
        }

        return array_unique($hosts);
    }

    public function getPhpVersion()
    {
        if (! empty($this->settings['php_version'])) {
            $phpVersion = $this->settings['php_version'];
        } else {
            $phpVersion = app(Config::class)->getUserConfig('default_php_version', config('php.default_version'));
        }

        return number_format($phpVersion, 1);
    }
}
