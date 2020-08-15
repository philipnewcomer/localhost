<?php

namespace App\Commands;

use App\Brew;
use App\Hosts;
use App\Nginx;
use App\Sites;
use App\Ssl;
use LaravelZero\Framework\Commands\Command;

class ReloadCommand extends Command
{
    protected $signature = 'reload';

    protected $description = 'Reloads the sites and restarts services.';

    public function handle(Brew $brew, Hosts $hosts, Nginx $nginx, Sites $sites, Ssl $ssl)
    {
        $hosts->setHosts($sites->getAllHosts());

        $ssl->generateHostsCertificate($sites->getAllHosts());

        $nginx->generateSiteConfigs();

        $this->line($brew->restartService('nginx'));
    }
}
