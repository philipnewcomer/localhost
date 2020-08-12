<?php

namespace App\Commands;

use App\Brew;
use App\Config;
use App\Nginx;
use App\Php;
use LaravelZero\Framework\Commands\Command;

class StartCommand extends Command
{
    protected $signature = 'start';

    protected $description = 'Boots up the system.';

    public function handle(Config $config, Nginx $nginx, Php $php)
    {
        $config->maybeCreateConfigDirectory();
        $nginx->generateSiteConfigs();
        $php->generateFpmConfigs();

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
