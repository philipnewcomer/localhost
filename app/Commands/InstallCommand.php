<?php

namespace App\Commands;

use App\Brew;
use App\Pecl;
use App\Php;
use LaravelZero\Framework\Commands\Command;

class InstallCommand extends Command
{
    protected $signature = 'install';

    protected $description = 'Installs required dependencies.';

    public function handle()
    {
        $this->ensureInstalledBrew('mailhog', 'Mailhog');
        $this->ensureInstalledBrew('mariadb', 'MariaDB');
        $this->ensureInstalledBrew('nginx', 'Nginx');
        foreach (config('php.versions') as $version) {
            $this->ensureInstalledBrew('php@' . $version, 'PHP ' . $version);
        }
        $this->ensureInstalledBrew('redis', 'Redis');

        $this->ensureInstalledPecl([
            'redis' => 'Redis',
            'xdebug' => 'Xdebug'
        ]);

        $this->info(sprintf(
            'Installation successful. Run `%s start` to boot up the system.',
            config('app.command')
        ));
    }

    protected function ensureInstalledBrew(string $formula, $label)
    {
        if (! app(Brew::class)->isInstalled($formula)) {
            $this->line(sprintf('Installing %s...', $label));
            app(Brew::class)->install($formula);
        } else {
            $this->line(sprintf('%s is already installed.', $label));
        }
    }

    protected function ensureInstalledPecl(array $packages)
    {
        foreach (config('php.versions') as $phpVersion) {
            app(Php::class)->link($phpVersion);

            foreach ($packages as $packageName => $packageLabel) {
                if (! app(Pecl::class)->isInstalled($packageName)) {
                    $this->line(sprintf('Installing %s for PHP %s...', $packageLabel, $phpVersion));
                    app(Pecl::class)->install($packageName);
                } else {
                    $this->line(sprintf('%s for PHP %s is already installed.', $packageLabel, $phpVersion));
                }
            }
        }
    }
}
