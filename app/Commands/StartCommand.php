<?php

namespace App\Commands;

use App\Brew;
use App\CommandLine;
use App\Config;
use App\Hosts;
use App\Nginx;
use App\Php;
use App\Sites;
use App\Ssl;
use LaravelZero\Framework\Commands\Command;

class StartCommand extends Command
{
    protected $signature = 'start';

    protected $description = 'Boots up the system.';

    public function handle(
        Brew $brew,
        CommandLine $commandLine,
        Config $config,
        Hosts $hosts,
        Nginx $nginx,
        Php $php,
        Sites $sites,
        Ssl $ssl
    ) {
        $commandLine->requestSudo();
        $config->maybeCreateConfigDirectory();

        $this->task('Updating hosts file', function () use ($hosts, $sites) {
            $hosts->setHosts($sites->getAllHosts());
        });

        $this->task('Generating configs', function () use ($nginx, $php) {
            $php->generateConfigs();
            $php->generateFpmConfigs();
            $nginx->generateSiteConfigs();
        });

        $this->task('Generating SSL certificate', function () use ($sites, $ssl) {
            $ssl->maybeGenerateCaCert();
            $ssl->generateHostsCertificate($sites->getAllHosts());
        });

        $this->task('Starting services', function () use ($brew) {
            $brew->startService('mailhog');
            $brew->startService('mariadb');
            $brew->startService('nginx');

            foreach (config('php.versions') as $version) {
                $brew->startService('php@' . $version);
            }

            $brew->startService('redis');
        });
    }
}
