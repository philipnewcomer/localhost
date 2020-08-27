<?php

namespace App\Commands;

use App\Brew;
use App\CommandLine;
use App\Hosts;
use App\Nginx;
use App\Php;
use LaravelZero\Framework\Commands\Command;

class StopCommand extends Command
{
    protected $signature = 'stop';

    protected $description = 'Shuts down the system.';

    public function handle(CommandLine $commandLine, Hosts $hosts, Nginx $nginx, Php $php)
    {
        $commandLine->requestSudo();


        $this->brewStopService('mailhog', 'Mailhog');
        $this->brewStopService('mariadb', 'MariaDB');
        $this->brewStopService('nginx', 'Nginx');

        foreach (config('php.versions') as $phpVersion) {
            $this->brewStopService(
                'php@' . $phpVersion,
                sprintf('PHP %s', $phpVersion)
            );
        }

        $this->brewStopService('redis', 'Redis');

        $this->task('Cleaning up generated configs', function () use ($nginx, $php) {
            $nginx->deleteSiteConfigs();
            $php->deleteConfigs();
            $php->deleteFpmConfigs();
        }, 'Waiting...');

        $this->task('Cleaning up hosts file', function () use ($hosts) {
            $hosts->clearHosts();
        }, 'Waiting...');
    }

    protected function brewStopService(string $service, string $label)
    {
        $this->task(sprintf('Stopping %s', $label), function () use ($service) {
            app(Brew::class)->stopService($service);
        }, 'Waiting...');
    }
}
