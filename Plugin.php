<?php

namespace ABWebDevelopers\ImageResize;

use System\Classes\PluginBase;
use ABWebDevelopers\ImageResize\Classes\Resizer;
use ABWebDevelopers\ImageResize\Commands\ImageResizeClear;
use ABWebDevelopers\ImageResize\Commands\ImageResizeGc;
use ABWebDevelopers\ImageResize\Models\Settings;
use ABWebDevelopers\ImageResize\ReportWidgets\ImageResizeClearWidget;
use Event;
use Illuminate\Support\Facades\Artisan;

class Plugin extends PluginBase
{
    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'abwebdevelopers.imageresize::lang.plugin.name',
                'description' => 'Manage default settings for the Image Resizer plugin',
                'category'    => 'Content',
                'icon'        => 'icon-image',
                'class'       => 'ABWebDevelopers\ImageResize\Models\Settings',
                'permissions' => ['abwebdevelopers.imageresize.access_settings'],
                'order'       => 500,
                'keywords'    => 'image resize resizing modify photo modifier'
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function registerPermissions()
    {
        return [
            'abwebdevelopers.imageresize.access_settings' => ['tab' => 'abwebdevelopers.imageresize::lang.permissions.tab', 'label' => 'abwebdevelopers.imageresize::lang.permissions.access_settings'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function boot()
    {
        Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
            if ($controller instanceof \System\Controllers\Settings) {
                // Check this is the settings page for this plugin:
                if ($params === ['abwebdevelopers', 'imageresize', 'settings']) {
                    // Add CSS (minor patch)
                    $controller->addCss('/plugins/abwebdevelopers/imageresize/assets/settings-patch.css');
                }
            }
        });

        if (Settings::cleanupOnCacheClear()) {
            Event::listen('cache:cleared', function () {
                Artisan::call('imageresize:clear');
            });
        }
    }

    /**
     * @inheritDoc
     */
    public function register()
    {
        $this->registerConsoleCommand('imageresize:gc', ImageResizeGc::class);
        $this->registerConsoleCommand('imageresize:clear', ImageResizeClear::class);
    }

    /**
     * @inheritDoc
     */
    public function registerSchedule($schedule)
    {
        // This is throttled by your settings, it won't necessarily clear all images every 5 minutes
        $schedule->command('imageresize:gc')->everyFiveMinutes();
    }

    public function registerReportWidgets()
    {
        return [
            ImageResizeClearWidget::class => [
                'label' => 'Clear Image Resizer Cache',
                'context' => 'dashboard',
            ],
        ];
    }
}
