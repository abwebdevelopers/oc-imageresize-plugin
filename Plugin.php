<?php

namespace ABWebDevelopers\ImageResize;

use System\Classes\PluginBase;
use ABWebDevelopers\ImageResize\Classes\Resizer;
use Event;

class Plugin extends PluginBase
{

    public function pluginDetails()
    {
        return [
            'name' => 'abwebdevelopers.imageresize::lang.plugin.name',
            'description' => 'abwebdevelopers.imageresize::lang.plugin.description',
            'author' => 'AB Web Developers',
            'icon' => 'icon-file-image-o',
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

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'abwebdevelopers.imageresize::lang.plugin.name',
                'description' => 'Manage default settings for the Image Resizer plugin',
                'category'    => 'Content',
                'icon'        => 'icon-image',
                'class'       => 'ABWebDevelopers\ImageResize\Models\Settings',
                'order'       => 500,
                'keywords'    => 'image resize resizing modify photo modifier'
            ]
        ];
    }

    public function boot() {
        Event::listen('backend.page.beforeDisplay', function($controller, $action, $params) {
            if ($controller instanceof \System\Controllers\Settings) {
                // Check this is the settings page for this plugin:
                if ($params === ['abwebdevelopers', 'imageresize', 'settings']) {
                    // Add CSS (minor patch)
                    $controller->addCss('/plugins/abwebdevelopers/imageresize/assets/settings-patch.css');
                }
            }
        });
    }

}
