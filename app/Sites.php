<?php

namespace App;

use Illuminate\Support\Facades\File;

class Sites
{
    /**
     * @return Site[]
     */
    public function getAll()
    {
        $sites = [];
        $siteDirectories = File::directories(config('sites.sites_dir'));

        foreach ($siteDirectories as $siteDirectory) {
            $sites[] = new Site($siteDirectory);
        }

        return $sites;
    }
}
