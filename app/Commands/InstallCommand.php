<?php

namespace App\Commands;

use App\Brew;
use App\CommandLine;
use App\Pecl;
use App\Php;
use LaravelZero\Framework\Commands\Command;

class InstallCommand extends Command
{
    protected $signature = 'install';

    protected $description = 'Installs required dependencies.';

    public function handle(CommandLine $commandLine)
    {
        $commandLine->requestSudo();

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

        $this->task('Setting MySQL root user password', function () use ($commandLine) {
            $sql = "ALTER USER 'root'@'localhost' IDENTIFIED BY 'root'; FLUSH PRIVILEGES";

            app(Brew::class)->startService('mariadb');
            $commandLine->run(sprintf('sudo mysql -u root -e "%s"', $sql));
        }, 'Waiting...');

        $this->info(sprintf(
            'Installation successful. Run `%s start` to boot up the system.',
            config('app.command')
        ));
    }

    protected function ensureInstalledBrew(string $formula, $label)
    {
        $this->task(sprintf('Installing %s', $label), function () use ($formula) {
            if (! app(Brew::class)->isInstalled($formula)) {
                app(Brew::class)->install($formula);
            }
        }, 'Waiting...');
    }

    protected function ensureInstalledPecl(array $packages)
    {
        foreach (config('php.versions') as $phpVersion) {
            app(Php::class)->link($phpVersion);

            foreach ($packages as $packageName => $packageLabel) {
                $title = sprintf('Installing %s for PHP %s', $packageLabel, $phpVersion);

                $this->task($title, function () use ($packageName) {
                    if (! app(Pecl::class)->isInstalled($packageName)) {
                        app(Pecl::class)->install($packageName);
                    }
                }, 'Waiting...');
            }
        }
    }
}
