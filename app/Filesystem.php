<?php

namespace App;

class Filesystem
{
    public function __construct(protected CommandLine $commandLine) {}

    public function put(string $path, string $contents): void
    {
        $this->commandLine->run(sprintf(
            'echo %s | sudo tee %s',
            escapeshellarg($contents),
            $path
        ));
    }

    public function delete(string $path): void
    {
        $this->commandLine->run(sprintf('sudo rm %s', $path));
    }
}
