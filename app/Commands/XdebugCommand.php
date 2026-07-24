<?php

namespace App\Commands;

use App\Brew;
use App\CommandLine;
use App\Config;
use App\Php;
use LaravelZero\Framework\Commands\Command;

class XdebugCommand extends Command
{
    protected $signature = 'xdebug {--disable} {--enable}';

    protected $description = 'Toggles the xDebug PHP extension.';

    public function handle(CommandLine $commandLine)
    {
        if ($this->option('disable') || $this->option('enable')) {
            if ($this->option('disable')) {
                $commandLine->run('sudo phpdismod xdebug');
            }

            if ($this->option('enable')) {
                $commandLine->run('sudo phpenmod xdebug');
            }
        } else {
            $xdebugEnabled = extension_loaded('xdebug');
            $this->line($xdebugEnabled ? 'Xdebug is currently enabled.' : 'Xdebug is currently disabled.');
        }
    }
}
