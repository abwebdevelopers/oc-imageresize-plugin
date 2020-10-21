<?php

namespace ABWebDevelopers\ImageResize;

use ABWebDevelopers\ImageResize\Classes\Resizer;
use ABWebDevelopers\ImageResize\Commands\ImageResizeClear;
use ABWebDevelopers\ImageResize\Commands\ImageResizeGc;
use ABWebDevelopers\ImageResize\Commands\ImageResizeResetPermalinks;
use ABWebDevelopers\ImageResize\Models\ImagePermalink;
use ABWebDevelopers\ImageResize\Models\Settings;
use ABWebDevelopers\ImageResize\ReportWidgets\ImageResizeClearWidget;
use App;
use Artisan;
use DB;
use Event;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\QueryException;
use Symfony\Component\Console\Output\ConsoleOutput;
use System\Classes\PluginBase;

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
                'resizePermalink' => function ($image, $identifier, $width, $height = null, $options = []) {
                    $resizer = new Resizer((string) $image);

                    $width = ($width !== null) ? (int) $width : null;
                    $height = ($height !== null) ? (int) $height : null;
                    $options = ($options instanceof Arrayable) ? $options->toArray() : (array) $options;

                    return $resizer->resizePermalink($identifier, $width, $height, $options)->permalink_url;
                },
                'modifyPermalink' => function ($identifier, $image, $options = []) {
                    $resizer = new Resizer((string) $image);

                    $width = null;
                    $height = null;
                    $options = ($options instanceof Arrayable) ? $options->toArray() : (array) $options;

                    return $resizer->resizePermalink($identifier, $width, $height, $options)->permalink_url;
                },
                'resize' => function ($image, $width, $height = null, $options = []) {
                    $resizer = new Resizer((string) $image);

                    $width = ($width !== null) ? (int) $width : null;
                    $height = ($height !== null) ? (int) $height : null;
                    $options = ($options instanceof Arrayable) ? $options->toArray() : (array) $options;

                    return $resizer->resize($width, $height, $options);
                },
                'modify' => function ($image, $options = []) {
                    $resizer = new Resizer((string) $image);

                    $width = null;
                    $height = null;
                    $options = ($options instanceof Arrayable) ? $options->toArray() : (array) $options;

                    return $resizer->resize($width, $height, $options);
                },
                'filterHtmlImageResize' => function ($html, $width, $height = null, $options = []) {
                    $html = (string) $html;
                    $width = ($width !== null) ? (int) $width : null;
                    $height = ($height !== null) ? (int) $height : null;
                    $options = ($options instanceof Arrayable) ? $options->toArray() : (array) $options;

                    return Resizer::parseFindReplaceImages($html, $width, $height, $options);
                },
                'filterHtmlImageModify' => function ($html, $options = []) {
                    $html = (string) $html;
                    $width = null;
                    $height = null;
                    $options = ($options instanceof Arrayable) ? $options->toArray() : (array) $options;

                    return Resizer::parseFindReplaceImages($html, $width, $height, $options);
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

        Event::listen('cache:cleared', function () {
            $this->ifDatabaseExists(function () {
                if (Settings::cleanupOnCacheClear()) {
                    if (App::runningInConsole()) {
                        $output = new ConsoleOutput();
                        $output->writeln('<info>Imagesizer: Deleting cached resized images...</info>');
                    }

                    Artisan::call('imageresize:clear');

                    if (App::runningInConsole()) {
                        $output->writeln('<info>Imagesizer: ' . Artisan::output() . '</info>');
                    }
                }
            });
        });
    }

    /**
     * @inheritDoc
     */
    public function register()
    {
        $this->registerConsoleCommand('imageresize:gc', ImageResizeGc::class);
        $this->registerConsoleCommand('imageresize:clear', ImageResizeClear::class);
        $this->registerConsoleCommand('imageresize:reset-permalink', ImageResizeResetPermalinks::class);
    }

    /**
     * @inheritDoc
     */
    public function registerSchedule($schedule)
    {
        // This is throttled by your settings, it won't necessarily clear all images every 5 minutes
        $schedule->command('imageresize:gc')->everyFiveMinutes();
    }

    /**
     * @inheritDoc
     */
    public function registerReportWidgets()
    {
        return [
            ImageResizeClearWidget::class => [
                'label' => 'Clear Image Resizer Cache',
                'context' => 'dashboard',
            ],
        ];
    }

    /**
     * Run the callback only if/when the database exists (and system_settings table exists).
     *
     * @param \Closure $callback
     * @return mixed
     */
    public function ifDatabaseExists(\Closure $callback)
    {
        $canConnectToDatabase = false;
        try {
            // Test database connection (throw exception if no DB is configured yet)
            $canConnectToDatabase = DB::table('system_settings')->exists();
        } catch (QueryException $e) {
        }

        if ($canConnectToDatabase) {
            return $callback();
        }
    }
}
