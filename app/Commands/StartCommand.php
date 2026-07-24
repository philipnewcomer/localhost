<?php

namespace App\Commands;

use App\Brew;
use App\CommandLine;
use App\Config;
use App\Hosts;
use App\Nginx;
use App\Php;
use App\Sites;
use App\Ssl;
use Exception;
use LaravelZero\Framework\Commands\Command;
use LaravelZero\Framework\Components\Logo\FigletString;

class StartCommand extends Command
{
    protected $signature = 'start';

    protected $description = 'Boots up the system.';

    public function handle(
        CommandLine $commandLine,
        Config $config,
        Hosts $hosts,
        Nginx $nginx,
        Php $php,
        Sites $sites,
        Ssl $ssl
    ) {
        $commandLine->requestSudo();
        $config->maybeCreateConfigDirectory();

        $this->task('Updating hosts file', function () use ($hosts, $sites) {
            $hosts->setHosts($sites->getAllHosts());
        }, 'Waiting...');

        $this->task('Generating configs', function () use ($nginx, $php) {
            $php->generateConfigs();
            $php->generateFpmConfigs();
            //$php->cleanDefaultIniFileIncludes();
            $nginx->generateSiteConfigs();
        }, 'Waiting...');

        $this->task('Generating SSL certificate', function () use ($sites, $ssl) {
            $ssl->generateCaCert();
            $ssl->generateHostsCertificate($sites->getAllHosts());
        }, 'Waiting...');

        if ('linux' === config('environment.platform')) {
            $this->task(
                sprintf('Adding %s to the www-data group', config('environment.user')),
                function () use ($commandLine) {
                    $commandLine->run(sprintf('sudo usermod -a -G %s www-data', config('environment.user')));
                }
            );
        }

        $this->task('Creating MySQL user', function () use ($commandLine) {
            //app(Brew::class)->startService('mariadb');

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

            //app(Brew::class)->stopService('mariadb');
        }, 'Waiting...');

        /*
        $this->brewStartService('mailhog', 'Mailhog');
        $this->brewStartService('mariadb', 'MariaDB');
        $this->brewStartService('nginx', 'Nginx');

        foreach (config('php.versions') as $phpVersion) {
            $this->brewStartService(
                'php@' . $phpVersion,
                sprintf('PHP %s', $phpVersion)
            );
        }

        $this->brewStartService('redis', 'Redis');
        */
    }

    protected function brewStartService(string $service, string $label)
    {
        $this->task(sprintf('Starting %s', $label), function () use ($service) {
            app(Brew::class)->startService($service);
        }, 'Waiting...');
    }
}
