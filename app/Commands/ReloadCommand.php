<?php

namespace App\Commands;

use App\Brew;
use App\Nginx;
use App\Sites;
use App\Ssl;
use LaravelZero\Framework\Commands\Command;

class ReloadCommand extends Command
{
    protected $signature = 'reload';

    protected $description = 'Reloads the sites and restarts services.';

    public function handle(Brew $brew, Nginx $nginx, Sites $sites, Ssl $ssl)
    {
        $ssl->generateHostsCertificate($sites->getAllHosts());

        $nginx->generateSiteConfigs();

        $this->line($brew->restartService('nginx'));
    }
}
