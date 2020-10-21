<?php

namespace ABWebDevelopers\ImageResize\Models;

use ABWebDevelopers\ImageResize\Classes\Resizer;
use Illuminate\Database\Eloquent\Builder;
use Model;

class ImagePermalink extends Model
{
    public $table = 'abweb_imageresize_permalinks';

    /**
     * Define cast types for each modifier type
     *
     * @var array
     */
    protected $castModifiers = [
        'identifier' => 'string',
        'image' => 'string',
        'path' => 'string',
    ];

    public $jsonable = [
        'options',
    ];

    /**
     * Scope all results by those matching the identifier (should always be only one)
     *
     * @param Builder $query
     * @param string $identifier
     * @return void
     */
    public function scopeByIdentifier(Builder $query, string $identifier): void
    {
        $query->where('identifier', $identifier);
    }

    /**
     * Get the Image Permalink with this identifier if it exists
     *
     * @param string $identifier
     * @return ImagePermalink|null
     */
    public static function withIdentifer(string $identifier): ?ImagePermalink
    {
        return static::byIdentifier($identifier)->first();
    }

    /**
     * Does the resized version of this image exist?
     *
     * @return boolean
     */
    public function resizeExists(): bool
    {
        return !empty($this->path) && file_exists($this->absolute_path);
    }

    /**
     * Generate a short life hash for this file.
     * This will be where the image is stored in the storage path
     *
     * @return string
     */
    public function generateShortLifeHash(): string
    {
        return hash('sha256', $this->identifier . ':permalink:' . json_encode($this->options));
    }

    /**
     * Resize the image, if not already resized
     *
     * @return void
     */
    public function resize()
    {
        if ($this->resizeExists() === false) {
            $config = $this->options ?? [];

            $resizer = Resizer::using($this->image)
                ->setHash($hash = $this->generateShortLifeHash())
                ->setOptions($config)
                ->doResize();

            $path = $resizer->getRelativePath();

            list($mime, $format) = $resizer->detectFormat(true);

            $this->path = $path;
            $this->resized_at = now();
            $this->save();
        }

        return $this;
    }

    /**
     * Get the absolute path to the resized image
     *
     * @return string
     */
    public function getAbsolutePathAttribute(): string
    {
        return base_path($this->path);
    }

    /**
     * Get the permalink URL for this image
     *
     * @return string
     */
    public function getPermalinkUrlAttribute(): string
    {
        return url('/imageresizestatic/' . $this->identifier . '.' . $this->extension);
    }

    /**
     * Render this image to screen, now.
     *
     * @return void
     */
    public function render()
    {
        $this->resize(); // if not resized

        header('Content-Type: ' . $this->mime);
        echo file_get_contents($this->absolute_path);
        exit();
    }

    public static function fromResizer(string $identifier, Resizer $resizer): ImagePermalink
    {
        $that = static::withIdentifer($identifier);

        if ($that === null) {
            $that = new static();

            list($mime, $format) = $resizer->detectFormat(true);

            $that->identifier = $identifier;
            $that->image = $resizer->getImagePath();
            $that->mime_type = 'image/' . $mime;
            $that->extension = $format;
            $that->options = $resizer->getOptions();

            $that->save();
        }

        return $that;
    }
}
