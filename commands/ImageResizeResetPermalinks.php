<?php

namespace ABWebDevelopers\ImageResize\Commands;

use ABWebDevelopers\ImageResize\Classes\Resizer;
use ABWebDevelopers\ImageResize\Models\ImagePermalink;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImageResizeResetPermalinks extends Command
{
    protected $name = 'imageresize:reset-permalinks';

    protected $description = 'Delete all permalink configurations in case of needing to regenerate all images using new modifications. If all identifiers are the same, this should have no major affect on the website.';

    public function handle()
    {
        $deleted = ImagePermalink::count();

        // Delete all permalinks
        ImagePermalink::query()->delete();

        $this->info('Successfully deleted ' . $deleted . ' ' . Str::plural('permalinks', $deleted));
    }
}
