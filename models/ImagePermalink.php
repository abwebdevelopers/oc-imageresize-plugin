<?php

namespace ABWebDevelopers\ImageResize\Models;

use ABWebDevelopers\ImageResize\Classes\Resizer;
use Illuminate\Contracts\Support\Arrayable;
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
     * When casting this object to string, retrieve the Permalink URL
     *
     * @return string
     */
    public function __toString()
    {
        return $this->permalink_url;
    }

    /**
     * Set default for options (empty array) and path (empty string) on save
     *
     * @return void
     */
    public function beforeSave()
    {
        if (is_null($this->options)) {
            $this->options = [];
        }

        if (is_null($this->path)) {
            $this->path = '';
        }
    }

    /**
     * Get an ImagePermalink class by the given identifier (and provide
     * defaults for when not resized yet: width, height, options)
     *
     * @param string $identifier
     * @param string $image
     * @param integer $width
     * @param integer $height
     * @param array $options
     * @return ImagePermalink
     */
    public static function getPermalink(string $identifier, string $image, int $width = null, int $height = null, array $options = []): ImagePermalink
    {
        $resizer = new Resizer((string) $image);

        $width = ($width !== null) ? (int) $width : null;
        $height = ($height !== null) ? (int) $height : null;
        $options = ($options instanceof Arrayable) ? $options->toArray() : (array) $options;

        return $resizer->resizePermalink($identifier, $width, $height, $options)->permalink_url;
    }

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
        header('Content-Length: ' . filesize($this->absolute_path));
        echo file_get_contents($this->absolute_path);
        exit();
    }

    /**
     * Get a default 404 image not found mock permalink
     *
     * @return ImagePermalink
     */
    public static function defaultNotFound(): ImagePermalink
    {
        $identifier = '404';
        $resizer = Resizer::using('');

        return static::fromResizer($identifier, $resizer);
    }

    /**
     * Initialise an ImagePermalink from the given Resizer instance
     *
     * @param string $identifier
     * @param Resizer $resizer
     * @return ImagePermalink
     */
    public static function fromResizer(string $identifier, Resizer $resizer): ImagePermalink
    {
        $that = static::withIdentifer($identifier);

        if ($that === null) {
            $that = new static();

            $that->identifier = $identifier;
            $that->image = $resizer->getImagePath();

            list($mime, $format) = $resizer->detectFormat(true);

            $that->mime_type = 'image/' . $mime;
            $that->extension = $format;
            $that->options = $resizer->getOptions();

            $that->save();
        }

        return $that;
    }
}
