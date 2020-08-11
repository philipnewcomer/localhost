<?php

namespace App;

use Exception;

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
            trim($this->commandLine->run('brew list'))
        );

        return in_array($formula, $installedFormula);
    }
}
