<?php

namespace App;

use Illuminate\Support\Facades\File;

class Nginx
{
    public function generateSiteConfigs()
    {
        $siteConfigs = [];
        $siteConfigsFilePath = sprintf(
            '%s/%s.conf',
            config('nginx.sites_config_directory'),
            config('app.command')
        );

        foreach (app(Sites::class)->getAll() as $site) {
            $siteConfigs[] = $this->getConfig($site);
        }

        File::put($siteConfigsFilePath, join("\n", $siteConfigs));
    }

    public function deleteSiteConfigs()
    {
        $siteConfigsFilePath = sprintf(
            '%s/%s.conf',
            config('nginx.sites_config_directory'),
            config('app.command')
        );

        if (File::exists($siteConfigsFilePath)) {
            File::delete($siteConfigsFilePath);
        }
    }

    public function getConfig(Site $site)
    {
        $config = File::get(base_path('stubs/nginx-site.conf'));

        $replace = [
            'certPath' => sprintf('%s/%s.crt', config('environment.config_directory_path'), config('app.command')),
            'certKeyPath' => sprintf('%s/%s.key', config('environment.config_directory_path'), config('app.command')),
            'serverName' => join(' ', $site->getHosts()),
            'phpSocketPath' => sprintf(
                '%s/php-%s.sock',
                config('php.socket_directory'),
                $site->getPhpVersion()
            ),
            'publicDirectoryPath' => $site->getPublicDirectoryPath(),
        ];

        foreach ($replace as $placeholder => $value) {
            $config = str_replace(
                sprintf('{%s}', $placeholder),
                $value,
                $config
            );
        }

        return $config;
    }
}
