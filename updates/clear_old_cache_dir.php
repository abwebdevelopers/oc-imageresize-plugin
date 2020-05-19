<?php

namespace ABWebDevelopers\Portfolio\Updates;

use ABWebDevelopers\ImageResize\Classes\Resizer;
use Schema;
use October\Rain\Database\Updates\Migration;
use ABWebDevelopers\ImageResize\Models\Settings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ClearOldCacheDir extends Migration
{
    public function up()
    {
        $oldpath = base_path('storage/app/media/imageresizecache');

        if (Settings::instance()->cache_directory !== $oldpath) {
            if (is_dir($oldpath)) {
                Resizer::clearFiles(null, $oldpath);

                File::deleteDirectory($oldpath);
                @unlink($oldpath);
            }
        }
    }

    public function down()
    {
    }
}
