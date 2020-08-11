<?php

namespace App\Commands;

use App\Brew;
use LaravelZero\Framework\Commands\Command;

class StopCommand extends Command
{
    protected $signature = 'stop';

    protected $description = 'Shuts down the system.';

    public function handle(Brew $brew)
    {
        $this->line($brew->stopService('mariadb'));
        $this->line($brew->stopService('nginx'));
        $this->line($brew->stopService('redis'));
    }
}
