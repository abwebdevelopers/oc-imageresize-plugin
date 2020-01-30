<?php

namespace ABWebDevelopers\ImageResize\Commands;

use ABWebDevelopers\ImageResize\Classes\Resizer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImageResizeClear extends Command
{
    protected $name = 'imageresize:clear';

    protected $description = 'Clear all resized images.';

    public function handle()
    {
        $deleted = Resizer::clearFiles();

        $this->info('Successfully deleted ' . $deleted . ' ' . Str::plural('file', $deleted));
    }
}
