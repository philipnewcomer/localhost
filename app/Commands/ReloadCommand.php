<?php

namespace App\Commands;

use App\Brew;
use App\CommandLine;
use App\Hosts;
use App\Nginx;
use App\Sites;
use App\Ssl;
use LaravelZero\Framework\Commands\Command;

class ReloadCommand extends Command
{
    protected $signature = 'reload';

    protected $description = 'Reloads the sites and restarts services.';

    public function handle(Brew $brew, CommandLine $commandLine, Hosts $hosts, Nginx $nginx, Sites $sites, Ssl $ssl)
    {
        $commandLine->requestSudo();

        $this->task('Updating hosts file', function () use ($hosts, $sites) {
            $hosts->setHosts($sites->getAllHosts());
        });

        $this->task('Generating SSL certificate', function () use ($sites, $ssl) {
            $ssl->generateHostsCertificate($sites->getAllHosts());
        });

        $this->task('Generating site configs', function () use ($nginx) {
            $nginx->generateSiteConfigs();
        });

        $this->task('Restarting Nginx', function () use ($brew) {
            $brew->restartService('nginx');
        });
    }
}
