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

    public function handle(Brew $brew, CommandLine $commandLine, Hosts $hosts, Nginx $nginx, Php $php)
    {
        $commandLine->requestSudo();

        $this->task('Stopping services', function () use ($brew) {
            $brew->stopService('mailhog');
            $brew->stopService('mariadb');
            $brew->stopService('nginx');

            foreach (config('php.versions') as $version) {
                $brew->stopService('php@' . $version);
            }

            $brew->stopService('redis');
        });

        $this->task('Cleaning up generated configs', function () use ($nginx, $php) {
            $nginx->deleteSiteConfigs();
            $php->deleteConfigs();
            $php->deleteFpmConfigs();
        });

        $this->task('Cleaning up hosts file', function () use ($hosts) {
            $hosts->clearHosts();
        });
    }
}
