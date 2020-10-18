<?php

namespace App;

use Illuminate\Support\Facades\File;
use Phar;

class Stub
{
    public function get(string $filename, array $replace = []): string
    {
        $path = Phar::running()
            ? Phar::running() . '/stubs/' . $filename
            : base_path('stubs/' . $filename);

        $contents = File::get($path);

        foreach ($replace as $placeholder => $value) {
            $contents = str_replace(
                sprintf('{%s}', $placeholder),
                $value,
                $contents
            );
        }

        return $contents;
    }
}
