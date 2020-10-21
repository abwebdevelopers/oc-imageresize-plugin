<?php

namespace ABWebDevelopers\ImageResize\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreatePermalinksTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('abweb_imageresize_permalinks') === false) {
            Schema::create('abweb_imageresize_permalinks', function ($table) {
                $table->increments('id');

                $table->text('identifier');
                $table->text('image');
                $table->string('mime_type');
                $table->string('extension');
                $table->text('options')->default('{}');
                $table->text('path')->nullable()->default(null);
                $table->dateTime('resized_at')->nullable()->default(null);

                $table->timestamps();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('abweb_imageresize_permalinks') === true) {
            Schema::dropTable('abweb_imageresize_permalinks');
        }
    }
}
