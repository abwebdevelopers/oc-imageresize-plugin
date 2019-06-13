<?php
namespace ABWebDevelopers\ImageResize;

use System\Classes\PluginBase;
use ABWebDevelopers\ImageResize\Classes\Resizer;

class Plugin extends PluginBase
{

    public function pluginDetails()
    {
        return [
            'name' => 'abwebdevelopers.imagresize::lang.plugin.name',
            'description' => 'abwebdevelopers.imagresize::lang.plugin.description',
            'author' => 'AB Web Developers',
            'icon' => 'icon-files-o',
            'homepage' => 'https://abweb.com.au'
        ];
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'resize' => function ($image, $width, $height = null, $options = []) {
                    $resizer = new Resizer((string) $image);
                    return $resizer->resize((int) $width, (int) $height, (array) $options);
                },
                'modify' => function ($image, $options = []) {
                    $resizer = new Resizer((string) $image);
                    return $resizer->resize(null, null, (array) $options);
                }
            ]
        ];
    }

}
