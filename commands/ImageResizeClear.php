<?php

namespace ABWebDevelopers\ImageResize\Commands;

use ABWebDevelopers\ImageResize\Classes\Resizer;
use ABWebDevelopers\ImageResize\Models\Settings;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImageResizeClear extends Command
{
    protected $name = 'imageresize:clear';

    protected $description = 'Clear all resized images';

    public function handle()
    {
        $minAge = Carbon::now()->modify('-' . Settings::getAgeToDelete());

        $deleted = Resizer::clearFiles($minAge);

        $this->info('Successfully deleted ' . $deleted . ' ' . Str::plural('file', $deleted));
    }
}
