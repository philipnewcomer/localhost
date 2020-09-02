<?php

namespace App;

use Illuminate\Support\Facades\File;
use Phar;

class Stub
{
    public function get(string $filename): string
    {
        $path = 'stubs/' . $filename;

        if (! Phar::running()) {
            $path = base_path($path);
        }

        return File::get($path);
    }
}
