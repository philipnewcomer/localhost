<?php

namespace App;

class Brew
{
    /**
     * @var CommandLine
     */
    public $commandLine;

    public function __construct(CommandLine $commandLine)
    {
        $this->commandLine = $commandLine;
    }

    public function install(string $formula)
    {
        return $this->commandLine->run(sprintf(
            'brew install %s',
            $formula
        ));
    }

    public function isInstalled(string $formula)
    {
        $installedFormula = explode(
            PHP_EOL,
            $this->commandLine->run('brew list --formula')
        );

        return in_array($formula, $installedFormula);
    }

    public function startService(string $formula)
    {
        return $this->commandLine->run(sprintf(
            'brew services start %s',
            $formula
        ));
    }

    public function stopService(string $formula)
    {
        return $this->commandLine->run(sprintf(
            'brew services stop %s',
            $formula
        ), function () {
            // Don't abort if the serice isn't running.
            // @todo find a better way to handle this.
        });
    }

    public function restartService(string $formula)
    {
        return $this->commandLine->run(sprintf(
            'brew services restart %s',
            $formula
        ));
    }
}
