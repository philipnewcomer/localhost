<?php

namespace App;

class Pecl
{
    /**
     * @var CommandLine
     */
    public $commandLine;

    public function __construct(CommandLine $commandLine)
    {
        $this->commandLine = $commandLine;
    }

    public function install(string $package)
    {
        return $this->commandLine->run(sprintf(
            'pecl install %s',
            $package
        ));
    }

    public function isInstalled(string $package)
    {
        $listCommandLines = explode(
            PHP_EOL,
            $this->commandLine->run('pecl list')
        );

        foreach ($listCommandLines as $line) {
            if (preg_match(sprintf('/^%s.*?stable$/', $package), $line)) {
                return true;
            }
        }

        return false;
    }
}
