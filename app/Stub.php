<?php

namespace App;

use Illuminate\Support\Facades\File;
use Phar;

class Stub
{
    public function get(string $filename): string
    {
        $path = Phar::running()
            ? Phar::running() . '/stubs/' . $filename
            : base_path('stubs/' . $filename);

        return File::get($path);
    }
}
