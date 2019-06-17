<?php namespace ABWebDevelopers\Portfolio\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use ABWebDevelopers\ImageResize\Models\Settings;

class SeedFilters extends Migration
{
    public function up()
    {
        $filters = Settings::get('filters');

        $hasThumbnail = false;
        $hasHero = false;

        foreach ($filters as $key => $value) {
            if ($value['code'] == 'thumbnail') {
                $hasThumbnail = true;
            } elseif ($value['code'] == 'hero') {
                $hasHero = true;
            }
        }

        if (!$hasThumbnail) {
            $filters[] = [
                "code" => "thumbnail",
                "description" => "Basic thumbnail filter",
                "rules" => [
                    [
                        "modifier" => "max_width",
                        "value" => "500",
                    ],
                    [
                        "modifier" => "max_height",
                        "value" => "500",
                    ],
                    [
                        "modifier" => "brightness",
                        "value" => "50",
                    ],
                    [
                        "modifier" => "background",
                        "value" => "#fff",
                    ],
                    [
                        "modifier" => "greyscale",
                        "value" => "true",
                    ],
                    [
                        "modifier" => "quality",
                        "value" => "60",
                    ],
                    [
                        "modifier" => "format",
                        "value" => "jpg",
                    ],
                    [
                        "modifier" => "mode",
                        "value" => "cover",
                    ],
                ],
            ];
        }

        if (!$hasHero) {
            $filters[] = [
                "code" => "hero",
                "description" => "Standard hero filter",
                "rules" => [
                    [
                        "modifier" => "width",
                        "value" => "1920",
                    ],
                    [
                        "modifier" => "height",
                        "value" => "500",
                    ],
                    [
                        "modifier" => "mode",
                        "value" => "cover",
                    ],
                    [
                        "modifier" => "quality",
                        "value" => "80",
                    ],
                    [
                        "modifier" => "format",
                        "value" => "jpg",
                    ],
                ],
            ];
        }
    }

    public function down()
    {

    }
}
