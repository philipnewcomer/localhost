<?php

namespace App\Commands;

use App\Brew;
use App\Config;
use App\Nginx;
use App\Php;
use App\Sites;
use App\Ssl;
use LaravelZero\Framework\Commands\Command;

class StartCommand extends Command
{
    protected $signature = 'start';

    protected $description = 'Boots up the system.';

    public function handle(Config $config, Nginx $nginx, Php $php, Sites $sites, Ssl $ssl)
    {
        $config->maybeCreateConfigDirectory();
        $php->generateFpmConfigs();

        $ssl->maybeGenerateCaCert();
        $ssl->generateHostsCertificate($sites->getAllHosts());

        $nginx->generateSiteConfigs();

        $this->startServices();
    }

    protected function startServices()
    {
        $this->line(app(Brew::class)->startService('mariadb'));
        $this->line(app(Brew::class)->startService('nginx'));
        foreach (config('php.versions') as $version) {
            $this->line(app(Brew::class)->startService('php@' . $version));
        }
        $this->line(app(Brew::class)->startService('redis'));
    }
}
