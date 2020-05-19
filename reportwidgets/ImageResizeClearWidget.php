<?php

namespace ABWebDevelopers\ImageResize\ReportWidgets;

use ABWebDevelopers\ImageResize\Models\Settings;
use Backend\Classes\ReportWidgetBase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use October\Rain\Support\Facades\Flash;

class ImageResizeClearWidget extends ReportWidgetBase
{
    public function defineProperties()
    {
        return [
            'showGcInterval' => [
                'title' => 'abwebdevelopers.imageresize::lang.widgets.clear.showGcInterval',
                'default' => true,
                'type' => 'checkbox',
            ]
        ];
    }

    /**
     * Default handler: Render the widget
     *
     * @return void
     */
    public function render()
    {
        return $this->makePartial('default');
    }

    /**
     * AJAX callback: Clear the cache
     *
     * @return void
     */
    public function onClear()
    {
        $from = $this->directorySize();
        Artisan::call('imageresize:clear');
        $to = $this->directorySize();

        $size = $this->formatSize($from - $to);
        Flash::success(Lang::get('abwebdevelopers.imageresize::lang.widgets.clear.cleared', [
            'size' => $size
        ]));

        return [
            'partial' => $this->render(),
        ];
    }

    /**
     * Get the total size (in bytes) of all resized images
     *
     * @return int
     */
    protected function directorySize(): int
    {
        $size = 0;
        $path = Settings::getBasePath();

        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Format the bytes to human readable form
     *
     * @param int $size
     * @param int $precision
     * @return string
     */
    protected function formatSize(int $size, int $precision = 2): string
    {
        if (empty($size)) {
            return '0B';
        }

        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];

        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }

    /**
     * Get the title of the widget
     *
     * @return string
     */
    public function title(): string
    {
        return 'Image Resize Cache';
    }

    /**
     * Get the size of all cached images (formatted)
     *
     * @return string
     */
    public function size(): string
    {
        return $this->formatSize($this->directorySize());
    }

    /**
     * Get the configured cache clear interval
     *
     * @return string|null
     */
    public function garbageCollectionInterval(): ?string
    {
        return Settings::getAgeToDelete();
    }

    /**
     * Should the garbage collection interval be displayed in this widget?
     *
     * @return boolean
     */
    public function showGarbageCollectionInterval(): bool
    {
        return (bool) $this->property('showGcInterval', false);
    }
}
