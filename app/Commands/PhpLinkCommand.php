<?php

namespace App\Commands;

use App\Php;
use LaravelZero\Framework\Commands\Command;

class PhpLinkCommand extends Command
{
    protected $signature = 'php:link {phpVersion}';

    protected $description = 'Links the specified version of PHP for use in the command line.';

    public function handle()
    {
        $phpVersion = $this->argument('phpVersion');

        if (! in_array($phpVersion, config('php.versions'))) {
            $this->error('Invalid PHP version.');
            return;
        }

        $phpVersion = sprintf('%0.1f', $phpVersion);

        app(Php::class)->link($phpVersion);

        $this->info(sprintf('PHP successfully linked to %s.', $phpVersion));
    }
}
