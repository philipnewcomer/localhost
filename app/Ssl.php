<?php

namespace App;

use Illuminate\Support\Facades\File;

class Ssl
{
    protected const CERT_ORGANIZATION_NAME = 'Localhost Self Signed Organization';
    protected const CERT_COMMON_NAME = 'Localhost Self Signed Certificate Authority';
    protected const CERT_EMAIL = 'admin@localhost';

    /**
     * @var CommandLine
     */
    protected $commandLine;

    public function __construct(CommandLine $commandLine)
    {
        $this->commandLine = $commandLine;
    }

    public function maybeGenerateCaCert()
    {
        $caKeyPath = sprintf('%s/%s-ca.key', config('environment.config_directory_path'), config('app.command'));
        $caPemPath = sprintf('%s/%s-ca.pem', config('environment.config_directory_path'), config('app.command'));

        if (File::exists($caPemPath) && File::exists($caKeyPath)) {
            return;
        }

        $this->commandLine->run(sprintf(
            'sudo security delete-certificate -c "%s" /Library/Keychains/System.keychain',
            self::CERT_COMMON_NAME
        ), function () {
            // Don't die on error.
        });

        $subjArgs = sprintf(
            '/C=/ST=/O=%s/localityName=/commonName=%s/organizationalUnitName=/emailAddress=%s/',
            self::CERT_ORGANIZATION_NAME,
            self::CERT_COMMON_NAME,
            self::CERT_EMAIL
        );

        $this->commandLine->run(sprintf(
            'openssl req -new -newkey rsa:2048 -days 3650 -nodes -x509 -subj "%s" -keyout "%s" -out "%s"',
            $subjArgs,
            $caKeyPath,
            $caPemPath
        ));

        $this->commandLine->run(sprintf(
            'sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain "%s"',
            $caPemPath
        ));
    }

    public function generateHostsCertificate(array $hosts)
    {
        $caKeyPath = sprintf('%s/%s-ca.key', config('environment.config_directory_path'), config('app.command'));
        $caPemPath = sprintf('%s/%s-ca.pem', config('environment.config_directory_path'), config('app.command'));

        $crtPath = sprintf('%s/%s.crt', config('environment.config_directory_path'), config('app.command'));
        $keyPath = sprintf('%s/%s.key', config('environment.config_directory_path'), config('app.command'));
        $csrPath = sprintf('%s/%s.csr', config('environment.config_directory_path'), config('app.command'));

        $sslConfigPath = sprintf('%s/openssl.conf', config('environment.config_directory_path'));

        /**
         * 1. Create key.
         */

        $this->commandLine->run(sprintf(
            'openssl genrsa -out "%s" 2048',
            $keyPath
        ));

        /**
         * 2. Create SSL config file.
         */

        $hostNamesConfig = '';
        foreach ($hosts as $hostIndex => $host) {
            $hostNamesConfig .= sprintf('DNS.%s = %s', $hostIndex + 1, $host) . PHP_EOL;
        }

        $sslConfig = File::get('stubs/openssl.conf');
        $sslConfig = str_replace('{hostNamesConfig}', $hostNamesConfig, $sslConfig);
        File::put($sslConfigPath, $sslConfig);

        /**
         * 3. Create signing request.
         */

        $subjArgs = sprintf(
            '/C=/ST=/O=%s/localityName=/commonName=%s/organizationalUnitName=/emailAddress=%s/',
            self::CERT_ORGANIZATION_NAME,
            self::CERT_COMMON_NAME,
            self::CERT_EMAIL
        );

        $this->commandLine->run(sprintf(
            'openssl req -new -key "%s" -subj "%s" -config "%s" -out "%s"',
            $keyPath,
            $subjArgs,
            $sslConfigPath,
            $csrPath
        ));

        /**
         * 4. Create certificate.
         */

        $this->commandLine->run(sprintf(
            'openssl x509 -req -sha256 -days 365 -CA "%s" -CAkey "%s" -CAcreateserial -in "%s" -out "%s" -extensions v3_req -extfile "%s"',
            $caPemPath,
            $caKeyPath,
            $csrPath,
            $crtPath,
            $sslConfigPath
        ));

        /**
         * 5. Clean up.
         */

        if (File::exists($csrPath)) {
            File::delete($csrPath);
        }

        if (File::exists($sslConfigPath)) {
            File::delete($sslConfigPath);
        }
    }
}
