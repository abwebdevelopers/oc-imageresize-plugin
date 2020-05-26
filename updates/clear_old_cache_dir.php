<?php

namespace ABWebDevelopers\Portfolio\Updates;

use ABWebDevelopers\ImageResize\Classes\Resizer;
use ABWebDevelopers\ImageResize\Models\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use October\Rain\Database\Updates\Migration;

class ClearOldCacheDir extends Migration
{
    public function up()
    {
        // Check that the settings for this plugin exist
        if (DB::table('system_settings')->where('item', 'abwebdevelopers_imageresize')->exists()) {
            $oldpath = base_path('storage/app/media/imageresizecache');

            if (Settings::instance()->cache_directory !== $oldpath) {
                if (is_dir($oldpath)) {
                    Resizer::clearFiles(null, $oldpath);

                    File::deleteDirectory($oldpath);
                    @unlink($oldpath);
                }
            }
        }
    }

    public function down()
    {
    }
}
