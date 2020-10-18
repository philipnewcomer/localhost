<?php

namespace App;

use Illuminate\Support\Facades\File;

class Sites
{
    /**
     * @return Site[]
     */
    public function getAll(): array
    {
        $sites = [];
        $sitesDirectory = app(Config::class)->getUserConfig('sites_directory', config('sites.sites_dir'));

        if (! File::isDirectory($sitesDirectory)) {
            return $sites;
        }

        $siteDirectories = File::directories($sitesDirectory);

        foreach ($siteDirectories as $siteDirectory) {
            $sites[] = new Site($siteDirectory);
        }

        return $sites;
    }

    public function getAllHosts(): array
    {
        $hosts = ['localhost'];

        foreach ($this->getAll() as $site) {
            $hosts = array_merge($hosts, $site->getHosts());
        }

        return array_unique($hosts);
    }
}
