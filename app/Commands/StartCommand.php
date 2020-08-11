<?php

namespace App\Commands;

use App\Brew;
use LaravelZero\Framework\Commands\Command;

class StartCommand extends Command
{
    protected $signature = 'start';

    protected $description = 'Boots up the system.';

    public function handle(Brew $brew)
    {
        $this->line($brew->startService('mariadb'));
        $this->line($brew->startService('nginx'));
        $this->line($brew->startService('redis'));
    }
}
