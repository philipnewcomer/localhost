<?php

namespace App;

use Illuminate\Support\Facades\File;

class Hosts
{
    /**
     * @var CommandLine
     */
    protected $commandLine;

    public function __construct(CommandLine $commandLine)
    {
        $this->commandLine = $commandLine;
    }

    public function setHosts(array $hosts)
    {
        $hostsFileContent = $this->readHostsFileContent();

        $lines = collect($hosts)->map(function ($host) {
            return sprintf('127.0.0.1 %s', escapeshellcmd($host));
        })->join("\n");

        if (empty($lines)) {
            return;
        }

        $lines = $this->wrap("\n" . $lines . "\n");

        if (preg_match($this->getSearchRegex(), $hostsFileContent)) {
            $newHostsFileContent = preg_replace($this->getSearchRegex(), $lines, $hostsFileContent);
        } else {
            $newHostsFileContent = $hostsFileContent . "\n\n" . $lines;
        }

        $this->writeHostsFileContent($newHostsFileContent);
    }

    public function clearHosts()
    {
        $hostsFileContent = $this->readHostsFileContent();

        $newHostsFileContent = preg_replace($this->getSearchRegex(), $this->wrap("\n"), $hostsFileContent);

        if ($hostsFileContent === $newHostsFileContent) {
            return;
        }

        $this->writeHostsFileContent($newHostsFileContent);
    }

    protected function readHostsFileContent(): string
    {
        return $this->commandLine->run('sudo -- sh -c "cat /etc/hosts"');
    }

    protected function writeHostsFileContent(string $content)
    {
        $this->commandLine->run(sprintf(
            'sudo -- sh -c "echo \'%s\' > /etc/hosts"',
            $content
        ));
    }

    protected function getSearchRegex()
    {
        return sprintf(
            '/%s/',
            $this->wrap('([\S\s]*)?')
        );
    }

    protected function wrap(string $content)
    {
        return sprintf(
            '# Begin %1$s hosts%2$s# End %1$s hosts',
            config('app.command'),
            $content
        );
    }
}
