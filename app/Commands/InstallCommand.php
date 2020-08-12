<?php

namespace App\Commands;

use App\Brew;
use LaravelZero\Framework\Commands\Command;

class InstallCommand extends Command
{
    protected $signature = 'install';

    protected $description = 'Installs required dependencies.';

    public function handle()
    {
        $this->ensureInstalled('mariadb', 'MariaDB');
        $this->ensureInstalled('nginx', 'Nginx');
        foreach (config('php.versions') as $version) {
            $this->ensureInstalled('php@' . $version, 'PHP ' . $version);
        }
        $this->ensureInstalled('redis', 'Redis');

        $this->info(sprintf(
            'Installation successful. Run `%s start` to boot up the system.',
            config('app.command')
        ));
    }

    protected function ensureInstalled(string $formula, $label)
    {
        $brew = app(Brew::class);

        if (! $brew->isInstalled($formula)) {
            $this->line(sprintf('Installing %s...', $label));
            $brew->install($formula);
        } else {
            $this->line(sprintf('%s is already installed.', $label));
        }
    }
}
