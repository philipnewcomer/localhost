<?php

namespace App\Commands;

use App\Brew;
use App\Config;
use App\Php;
use LaravelZero\Framework\Commands\Command;

class XdebugCommand extends Command
{
    protected $signature = 'xdebug {--disable} {--enable}';

    protected $description = 'Toggles the xDebug PHP extension.';

    public function handle(Brew $brew, Config $config, Php $php)
    {
        if ($this->option('disable') || $this->option('enable')) {
            if ($this->option('disable')) {
                $config->setUserConfig('xdebugEnabled', false);
            }

            if ($this->option('enable')) {
                $config->setUserConfig('xdebugEnabled', true);
            }

            $this->task('Generating PHP configs', function () use ($php) {
                $php->generateConfigs();
            }, 'Waiting...');

            foreach (config('php.versions') as $phpVersion) {
                $this->task(sprintf('Restarting PHP %s', $phpVersion), function () use ($brew, $phpVersion) {
                    $brew->restartService('php@' . $phpVersion);
                }, 'Waiting...');
            }
        } else {
            $xdebugEnabled = extension_loaded('xdebug');
            $this->line($xdebugEnabled ? 'Xdebug is currently enabled.' : 'Xdebug is currently disabled.');
        }
    }
}
