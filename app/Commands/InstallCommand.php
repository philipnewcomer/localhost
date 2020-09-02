<?php

namespace App\Commands;

use App\Brew;
use App\CommandLine;
use App\Pecl;
use App\Php;
use Exception;
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

        $this->task('Creating MySQL user', function () use ($commandLine) {
            app(Brew::class)->startService('mariadb');

            // If this is the first time MariaDB has been started, there will be a bit of a delay before it will be
            // ready to accept connections.
            $timeout = now()->addSeconds(10);
            while (true) {
                $result = $commandLine->run('sudo mysqladmin ping --no-beep', function () {});

                if ('mysqld is alive' === $result) {
                    break;
                }

                if (now() > $timeout) {
                    throw new Exception('Timed out waiting for MySQL.');
                }

                sleep(1);
            }

            $sql = "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
                . " DELETE FROM mysql.user WHERE User='';"
                . " DROP DATABASE IF EXISTS test;"
                . " DELETE FROM mysql.db WHERE Db='test' OR Db='test\_%';"
                . " CREATE USER IF NOT EXISTS 'localhost'@'%' IDENTIFIED BY 'localhost';"
                . " GRANT ALL PRIVILEGES ON *.* to 'localhost'@'%';"
                . " FLUSH PRIVILEGES;";

            $commandLine->run(sprintf('sudo mysql -e "%s"', $sql));

            app(Brew::class)->stopService('mariadb');
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
