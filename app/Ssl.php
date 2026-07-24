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

    public function purgeCaStores()
    {
        $caCertificatesPemPath = sprintf('/usr/local/share/ca-certificates/%s-ca.crt', config('app.command'));
        $nssdbCertName = sprintf('%s root CA', config('app.command'));

        if ('linux' === config('environment.platform')) {
            $this->commandLine->run(sprintf(
                'certutil -D -d $HOME/.pki/nssdb -n "%s"',
                $nssdbCertName
            ), function () {
                // Don't die on error.
            });

            if (File::exists($caCertificatesPemPath)) {
                $this->commandLine->run(sprintf('sudo rm %s', $caCertificatesPemPath));
            }

            $this->commandLine->run('sudo update-ca-certificates --fresh');
        }

        if ('macos' === config('environment.platform')) {
            $this->commandLine->run(sprintf(
                'sudo security delete-certificate -c "%s" /Library/Keychains/System.keychain',
                self::CERT_COMMON_NAME
            ), function () {
                // Don't die on error.
            });
        }
    }

    public function generateCaCert()
    {
        $this->purgeCaStores();

        $caKeyPath = sprintf('%s/%s-ca.key', config('environment.config_directory_path'), config('app.command'));
        $caPemPath = sprintf('%s/%s-ca.pem', config('environment.config_directory_path'), config('app.command'));
        $caCertificatesPemPath = sprintf('/usr/local/share/ca-certificates/%s-ca.crt', config('app.command'));
        $nssdbCertName = sprintf('%s root CA', config('app.command'));

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

        if ('linux' === config('environment.platform')) {
            $this->commandLine->run(sprintf(
                'sudo ln -s %s %s',
                $caPemPath,
                $caCertificatesPemPath
            ));

            $this->commandLine->run('sudo update-ca-certificates');

            $this->commandLine->run(sprintf(
                'certutil -d sql:$HOME/.pki/nssdb -A -t "C,," -n "%s" -i %s',
                $nssdbCertName,
                $caPemPath
            ));
        }

        if ('macos' === config('environment.platform')) {
            $this->commandLine->run(sprintf(
                'sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain "%s"',
                $caPemPath
            ));

            $this->commandLine->run('brew postinstall ca-certificates');
        }
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

        $sslConfig = app(Stub::class)->get('openssl.conf', ['hostNamesConfig' => $hostNamesConfig]);
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

        $crtBundle = File::get($crtPath) . File::get($caPemPath);
        File::put($crtPath, $crtBundle);

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
