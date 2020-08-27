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
        }, 'Waiting...');

        $this->task('Generating configs', function () use ($nginx, $php) {
            $php->generateConfigs();
            $php->generateFpmConfigs();
            $nginx->generateSiteConfigs();
        }, 'Waiting...');

        $this->task('Generating SSL certificate', function () use ($sites, $ssl) {
            $ssl->maybeGenerateCaCert();
            $ssl->generateHostsCertificate($sites->getAllHosts());
        }, 'Waiting...');

        $this->brewStartService('mailhog', 'Mailhog');
        $this->brewStartService('mariadb', 'MariaDB');
        $this->brewStartService('nginx', 'Nginx');

        foreach (config('php.versions') as $phpVersion) {
            $this->brewStartService(
                'php@' . $phpVersion,
                sprintf('PHP %s', $phpVersion)
            );
        }

        $this->brewStartService('redis', 'Redis');
    }

    protected function brewStartService(string $service, string $label)
    {
        $this->task(sprintf('Starting %s', $label), function () use ($service) {
            app(Brew::class)->startService($service);
        }, 'Waiting...');
    }
}
