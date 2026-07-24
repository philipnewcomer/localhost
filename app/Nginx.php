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

        app(Filesystem::class)->put($siteConfigsFilePath, join("\n", $siteConfigs));
    }

    public function deleteSiteConfigs()
    {
        $siteConfigsFilePath = sprintf(
            '%s/%s.conf',
            config('nginx.sites_config_directory'),
            config('app.command')
        );

        if (File::exists($siteConfigsFilePath)) {
            app(Filesystem::class)->delete($siteConfigsFilePath);
        }
    }

    public function getConfig(Site $site)
    {
        if (! empty($customNginxConfig = $site->getCustomNginxConfig())) {
            return $customNginxConfig;
        }

        $replace = [
            'certPath' => sprintf('%s/%s.crt', config('environment.config_directory_path'), config('app.command')),
            'certKeyPath' => sprintf('%s/%s.key', config('environment.config_directory_path'), config('app.command')),
            'serverName' => join(' ', $site->getHosts()),
            'phpSocketPath' => app(Php::class)->getSocketPath($site->getPhpVersion()),
            'publicDirectoryPath' => $site->getPublicDirectoryPath(),
        ];

        return app(Stub::class)->get('nginx-site.conf', $replace);
    }
}
