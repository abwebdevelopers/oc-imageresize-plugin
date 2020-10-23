<?php

namespace ABWebDevelopers\ImageResize\Classes;

use ABWebDevelopers\ImageResize\Models\ImagePermalink;
use ABWebDevelopers\ImageResize\Models\Settings;
use Cache;
use Carbon\Carbon;
use Exception;
use Event;
use File;
use Intervention\Image\ImageManagerStatic as Image;
use Validator;
use Illuminate\Support\Str;

class Resizer
{
    public const CACHE_PREFIX = 'image_resize_';

    /**
     * A list of computed values to override $options
     *
     * @var array
     */
    protected $override = [];

    /**
     * The list of active options
     *
     * @var array
     */
    protected $options = [];

    /**
     * The list of cacheable options (i.e. those specified, excl defaults)
     *
     * @var array
     */
    protected $cacheableOptions = [];

    /**
     * The original image resource
     *
     * @var resource
     */
    protected $original;

    /**
     * The modified image resource
     *
     * @var resource
     */
    protected $im;

    /**
     * The path to the image
     *
     * @var string
     */
    protected $image;

    /**
     * Hash - The cache identifier
     *
     * @var string
     */
    protected $hash;

    /**
     * Format Cache - Used for caching the determined format and mime of the original/new images
     *
     * @var array
     */
    protected $formatCache = [];

    /**
     * Can this Resizer default the image (when the image doesn't exist, for example)
     *
     * @var boolean
     */
    protected $allowDefaultImage = true;

    /**
     * Construct the resizer class
     *
     * @param string $image
     */
    public function __construct(string $image = null, bool $doNotModifyPath = false)
    {
        $this->setImage($image, $doNotModifyPath);
    }

    /**
     * Instantiate an instance using an image path
     *
     * @param string|null $image
     * @return Resizer
     */
    public static function using(string $image = null): Resizer
    {
        $that = new static($image, true);

        return $that;
    }

    /**
     * Set the format cache
     *
     * @param array $cache
     * @return $this
     */
    public function setFormatCache(array $cache)
    {
        $this->formatCache = $cache;

        return $this;
    }

    /**
     * Specify the image to use
     *
     * @param string $image
     * @return $this
     */
    public function setImage(string $image = null, bool $doNotModifyPath = false)
    {
        if ($doNotModifyPath) {
            $this->image = $image;

            return $this;
        }

        if ($image !== null) {
            $absolutePath = false;

            // Support JSON objects containing path property, e.g: {"path":"USETHISPATH",...}
            if (substr($image, 0, 2) === '{"') {
                $attempt = json_decode($image);

                if (!empty($attempt->path)) {
                    $image = $attempt->path;
                }
            }

            // Get the domain
            $domain = $_SERVER['SERVER_NAME'] ?? '';
            if (empty($domain)) {
                $domain = parse_url(url()->to('/'), PHP_URL_HOST);
            }

            // Check if the image is an absolute url to the same server, if so get the storage path of the image
            $regex = '/^(?:https?:\/\/)?' . $domain . '(?::\d+)?\/(.+)$/';
            if (preg_match($regex, $image, $m)) {
                // Convert spaces, not going to urldecode as it will mess with pluses
                $image = base_path(str_replace('%20', ' ', $m[1]));
                $absolutePath = true;
            }

            // If not an absolute path, set it to an absolute path
            if (!$absolutePath) {
                $image = base_path(trim($image, '/'));
            }
        }

        // Set the image
        $this->image = $image;

        return $this;
    }

    /**
     * Get the path to the image (or default if necessary)
     *
     * @return string
     */
    public function getImagePath(): string
    {
        $image = $this->image;

        if ($this->allowDefaultImage) {
            // If the image is invalid, default to Image Not Found
            if ($image === null || $image === '' || !file_exists($image)) {
                $image = $this->getDefaultImage();
            }
        }

        return $image;
    }

    /**
     * Get the path to the image (relative path preferred)
     */
    public function getImagePathRelativePreferred(): string
    {
        $image = $this->getImagePath();
        $base = rtrim(base_path(), '/');

        if (Str::startsWith($image, $base)) {
            $image = ltrim(substr($image, strlen($base)), '/');
        }

        return $image;
    }

    /**
     * Retrieve the Image Not Found image
     *
     * @return string
     */
    protected function getDefaultImage(): string
    {
        // Retrieve the Image Not Found image from settings
        $image = Settings::get('image_not_found');

        // If available, apply it
        if (!empty($image)) {
            $image = base_path(ltrim(config('cms.storage.media.path'), '/')) . $image;
        }

        // If the image still doesn't exist, use the provided Image Not Found image
        if (!$image || !file_exists($image)) {
            $image = Settings::getDefaultImageNotFound(true);
        }

        // Use the default Image Not Found background, mode and quality
        $this->options['background'] = Settings::get('image_not_found_background', '#fff');
        if (Settings::get('image_not_found_transparent')) {
            unset($this->options['background']);
        }
        $format = Settings::get('image_not_found_format', 'auto');
        if ($format !== 'auto') {
            $this->options['format'] = $format;
        }
        $this->options['mode'] = Settings::get('image_not_found_mode', 'cover');
        $this->options['quality'] = Settings::get('image_not_found_quality', 65);

        return $image;
    }

    /**
     * Set some options for the image resizer
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * Set the hash for this file
     *
     * @param string $hash
     * @return $this
     */
    public function setHash(string $hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Initialise the resource - Creates the original and (to be) modified image resource entities
     *
     * @return $this
     */
    public function initResource()
    {
        if (empty($this->im)) {
            Image::configure([
                'driver' => $this->options['driver'] ?? 'gd'
            ]);

            try {
                // Default the image if it's a directory
                if (is_dir($this->image)) {
                    throw new \Exception('Image file does not exist (is directory)');
                }

                // Default the image if it doesn't exist
                if (is_dir($this->image)) {
                    throw new \Exception('Image file does not exist (not found)');
                }

                $this->im = $this->original = Image::make($this->image);
            } catch (\Exception $e) {
                if ($this->allowDefaultImage) {
                    $this->setFormatCache([]);
                    $this->im = $this->original = Image::make($this->image = $this->getDefaultImage());
                }
            }
        }

        return $this;
    }

    /**
     * Initialise the options - Maps options and uses default options as a base as well as
     * setting the hash to be used for caching
     *
     * @param array $options
     * @return $this
     */
    private function initOptions(array $options = null)
    {
        if ($options !== null) {
            // Allow options with key $k, in place of key $v
            $map = [
                'fill' => 'background',
                'grayscale' => 'greyscale',
                'colourise' => 'colorize'
            ];

            // Change them over
            foreach ($map as $k => $v) {
                if (isset($this->options[$k])) {
                    $options[$v] = $this->options[$k];
                    unset($options[$k]);
                }
            }

            // A couple predefined presets (deprecated, use filters now)
            if (!empty($options['preset'])) {
                switch ($options['preset']) {
                    case 'low':
                        $options['format'] = 'jpg';
                        $options['quality'] = 50;
                        break;
                    case 'medium':
                        $options['format'] = 'jpg';
                        $options['quality'] = 80;
                        break;
                    case 'high':
                        if (!empty($options['format'])) {
                            unset($options['format']);
                        }
                        $options['quality'] = 100;
                        break;
                }
            }

            // Check to see if a filter is being used for this image
            if (!empty($options['filter'])) {
                // If options were passed then use them to override any filters used
                $options = array_filter((array) $options);
                $this->override = array_merge($this->override, $options);

                // Now find it
                $availableFilters = Settings::get('filters');
                foreach ($availableFilters as $filter) {
                    if ($filter['code'] === $options['filter']) {
                        // Found it, now apply the rules to the options
                        foreach ($filter['rules'] as $rule) {
                            $options[$rule['modifier']] = $rule['value'];
                        }
                        break;
                    }
                }
            }

            // Apply overrides
            if (!empty($this->override)) {
                foreach ($this->override as $key => $value) {
                    $options[$key] = $value;
                }
            }
        } else {
            $options = [];
        }

        // Get defaults from settings
        $defaults = [
            'driver' => Settings::get('driver'),
            'mode' => Settings::get('mode'),
            'quality' => Settings::get('quality'),
            'format' => Settings::get('format'),
        ];

        // Don't cache defaults
        $this->cacheableOptions = $options;

        // Merge defaults and options
        $this->options = array_merge($defaults, $options);

        // Set hash based on image and options
        $this->hash = hash('sha256', $this->image . json_encode($this->options));

        return $this;
    }

    /**
     * Get the options defined in this resizer
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get the options defined in this resizer, excluding defaults
     *
     * @return array
     */
    public function getCacheableOptions(): array
    {
        return $this->cacheableOptions;
    }

    /**
     * Get the absolute physical path of the image
     *
     * @return string
     */
    public function getAbsolutePath(): string
    {
        return base_path($this->getRelativePath());
    }

    /**
     * Get the expected output extension
     *
     * @return string
     */
    public function outputExtension(): string
    {
        return $this->detectFormat(true)[1];
    }

    /**
     * Get the path relative to the base directory
     *
     * @return string
     */
    public function getRelativePath(): string
    {
        $rel = '/' . substr($this->hash, 0, 3) .
            '/' . substr($this->hash, 3, 3) .
            '/' . substr($this->hash, 6, 3) .
            '/' . $this->hash .
            '.' . $this->outputExtension();

        return Settings::getBasePath($rel);
    }

    /**
     * Get the URL for resizing this image for the first time.
     *
     * @return string
     */
    public function getFirstTimeUrl(): string
    {
        $url = '/imageresize/' . $this->hash . '.' . $this->outputExtension();
        $url = url($url);

        return $url;
    }

    /**
     * Get the URL of the resized (and cached) image.
     *
     * Simply returns a relative URL to the website.
     *
     * @return string
     */
    public function getCacheUrl(): string
    {
        $url = '/' . $this->getRelativePath();
        $url = url($url);

        return $url;
    }

    /**
     * Store the configuration in the cache, and retrieve the URL
     *
     * @return bool|string
     */
    public function storeCacheAndgetFirstTimeUrl()
    {
        Cache::remember(static::CACHE_PREFIX . $this->hash, Carbon::now()->addWeek(), function () {
            return [
                'image' => $this->image,
                'options' => $this->options,
                'formatCache' => $this->formatCache,
            ];
        });

        $cacheExists = $this->hasStoredFile();

        return ($cacheExists) ? $this->getCacheUrl() : $this->getFirstTimeUrl();
    }

    /**
     * Set the cache, storing the modified image to file and return the public facing path to it
     *
     * @return $this
     */
    public function storeResizedImage()
    {
        // Get absolute path
        $path = $this->getAbsolutePath();

        // Create directory if not exists
        $base = dirname($path);
        if (!file_exists($base)) {
            mkdir($base, 0775, true);
        }

        // Save to file
        $this->im->save($path, $this->options['quality'] ?? null);

        return $this;
    }

    /**
     * Resize - Optionally resize the image, and/or modify the image with options.
     *
     * Contrary to function name, this [as of v2.0] only returns a publicly accessible URL for the image.
     * Resizing happens in the public endpoint.
     *
     * @param int $width
     * @param int $height
     * @param array $options
     * @return string
     */
    public function resize(int $width = null, int $height = null, array $options = null): string
    {
        $width = ($width > 0) ? $width : null;
        $height = ($height > 0) ? $height : null;

        // Fill these keys in, as it'll be used to help identify the cache
        $options['width'] = $width;
        $options['height'] = $height;

        // Set options, set hash for cache
        $this->initOptions($options);

        // Get cache if exists
        return $this->storeCacheAndgetFirstTimeUrl();
    }

    /**
     * Resize - Optionally resize the image, and/or modify the image with options.
     *
     * This implements a permalink class and does not resize until first accessed
     *
     * @param int $width
     * @param int $height
     * @param array $options
     * @return ImagePermalink
     */
    public function resizePermalink(string $identifier, int $width = null, int $height = null, array $options = []): ImagePermalink
    {
        $identifier = trim($identifier, '/');

        $width = ($width > 0) ? $width : null;
        $height = ($height > 0) ? $height : null;

        // Fill these keys in, as it'll be used to help identify the cache
        $options['width'] = $width;
        $options['height'] = $height;

        // Don't need to cache this
        unset($options['permalink']);

        // Set options, set hash for cache
        $this->initOptions($options);

        return ImagePermalink::fromResizer($identifier, $this);
    }

    /**
     * Does the current file exist in the filesystem?
     *
     * @return bool
     */
    public function hasStoredFile(): bool
    {
        return file_exists($this->getAbsolutePath());
    }

    /**
     * Should the image be cached?
     *
     * @return bool
     */
    public function shouldCache(): bool
    {
        return !isset($this->options['cache']) || (bool) $this->options['cache'];
    }

    /**
     * Do the resizing (if applicable)
     *
     * @return $this
     */
    public function doResize()
    {
        if ($this->shouldCache() && $this->hasStoredFile()) {
            return $this;
        }

        $width = $this->options['width'] ?? null;
        $height = $this->options['height'] ?? null;

        $hasMinMaxConstraint = array_key_exists('min_height', $this->options) ||
            array_key_exists('max_height', $this->options) ||
            array_key_exists('min_width', $this->options) ||
            array_key_exists('max_width', $this->options);

        // Get the image resource entity if not already loaded
        $this->initResource();

        // If width or height is set, resize the image to it
        if (($width !== null) || ($height !== null) || $hasMinMaxConstraint) {
            $oheight = $this->original->height();
            $owidth = $this->original->width();
            $oratio = $owidth / $oheight;

            // Will the dimensions be the same?
            $same = ($width === null || $height === null);

            if ($width === null && $height !== null) {
                // Only the height was given

                if (!empty($this->options['min_height'])) {
                    $height = max($this->options['min_height'], $height);
                }

                if (!empty($this->options['max_height'])) {
                    $height = min($this->options['max_height'], $height);
                }

                $width = (int) ($height * $oratio);
            } elseif ($height === null && $width !== null) {
                // Only the width was given

                if (!empty($this->options['min_width'])) {
                    $width = max($this->options['min_width'], $width);
                }

                if (!empty($this->options['max_width'])) {
                    $width = min($this->options['max_width'], $width);
                }

                $height = (int) ($width / $oratio);
            } else if ($width === null && $height === null) { // Neither were given
                $height = $oheight;
                $width = $owidth;

                if (!empty($this->options['min_width'])) {
                    $width = max($this->options['min_width'], $width);
                }

                if (!empty($this->options['max_width'])) {
                    $width = min($this->options['max_width'], $width);
                }

                if (!empty($this->options['min_height'])) {
                    $height = max($this->options['min_height'], $height);
                }

                if (!empty($this->options['max_height'])) {
                    $height = min($this->options['max_height'], $height);
                }
            } else { // Both were given
                if (!empty($this->options['min_width'])) {
                    $width = max($this->options['min_width'], $width);
                }

                if (!empty($this->options['max_width'])) {
                    $width = min($this->options['max_width'], $width);
                }

                if (!empty($this->options['min_height'])) {
                    $height = max($this->options['min_height'], $height);
                }

                if (!empty($this->options['max_height'])) {
                    $height = min($this->options['max_height'], $height);
                }
            }

            // Determine the ratio, and whether or not its the same ratio thats being generated
            $ratio = $width / $height;
            $same = $same ?? ($ratio === $oratio);

            // What we'll resize the image to, before cropping (may differ from specified dimensions)
            $resizeWidth = $width;
            $resizeHeight = $height;

            // Use fit mode?
            $fit = false;
            // Get the position to fit to
            $fitPosition = $this->options['fit_position'] ?? 'center';

            // Allow upsizing of the image? (default: false)
            $allowUpsizing = (!empty($this->options['upsize']) && (bool) $this->options['upsize']);

            // Should the canvas be resized to the dimensions specified?
            $resizeCanvas = false;

            // Figure out what to do, crop, pad, etc
            if (!empty($this->options['mode'])) {
                switch ($this->options['mode']) {
                    case 'contain':
                        if ($same) {
                            break; // Nothing needs to be done
                        } elseif ($oratio > $ratio) {
                            // Was wider, is more thinner now, so calculate the height of the image ($height is now simply the canvas size)
                            $resizeHeight = $width / $oratio;
                            $resizeCanvas = true;
                        } else {
                            // Was taller, is more smaller now, so calculate the width of the image ($width is now simply the canvas size)
                            $resizeWidth = $height * $oratio;
                            $resizeCanvas = true;
                        }
                        break;
                    case 'cover':
                    case 'crop':
                        $fit = true;
                        break;
                    case 'auto':
                        $this->im->resizeCanvas($width, $height);
                        break;
                    case 'stretch':
                        // Use width and height, stretch to fit
                        break;
                    default:
                        //
                        break;
                }
            }

            // If using the fit mode, use fit
            if ($fit) {
                $this->im->fit($width, $height, function ($constraint) use ($allowUpsizing) {
                    if (!$allowUpsizing) {
                        $constraint->upsize(); // prevent upsizing
                    }
                }, $fitPosition);
            } else {
                // Otherwise resize using traditional resize method
                $this->im->resize($resizeWidth, $resizeHeight, function ($constraint) use ($allowUpsizing) {
                    if (!$allowUpsizing) {
                        $constraint->upsize(); // prevent upsizing
                    }
                });
            }

            // Resize the canvas to the specified dimensions (contain: adds padding)
            if ($resizeCanvas) {
                $this->im->resizeCanvas($width, $height);
            }
        }

        // Get the format / mime to export to (either original, or overridden)
        list($mime, $format) = $this->detectFormat(true);

        // If it's exporting to a flat image and no background is set, and it was transparent to start off with..
        if (($format !== 'png') && ($format !== 'webp') && empty($this->options['background']) && $this->detectAlpha()) {
            // Then fill in the background - Would be nice to guess the color but that's not my job
            $this->options['background'] = '#fff';
        }

        // Run the modifications on the image
        $this->modify();

        $this->storeResizedImage();

        return $this;
    }

    /**
     * Prevent the Resizer from defaulting the image
     *
     * @return void
     */
    public function preventDefaultImage()
    {
        $this->allowDefaultImage = false;

        return $this;
    }

    /**
     * Prevent the Resizer from defaulting the image
     *
     * @return void
     */
    public function allowDefaultImage()
    {
        $this->allowDefaultImage = false;

        return $this;
    }

    /**
     * Detect format of input file for default export format
     *
     * Return value is: [mime, format]
     * Example:         ['image/jpeg', 'jpg']
     *
     * @param  array $options
     * @return array
     */
    public function detectFormat(bool $useNewFormat = false): array
    {
        // Convert standard boolean to numeric boolean (for array access)
        $useNewFormat = ($useNewFormat) ? 1 : 0;

        // If it's already calculated these, then return the cached copy of it
        if (!empty($this->formatCache[$useNewFormat])) {
            return $this->formatCache[$useNewFormat];
        }

        // Determine whether or not to use the new format in
        if ($useNewFormat && !empty($this->options['format']) && ($this->options['format'] !== 'auto')) {
            $format = $this->options['format'];
        } else {
            if (File::exists($path = $this->getImagePath())) {
                $format = File::mimeType($path);
                $format = Str::after($format, '/');
            } else {
                // If the file doesn't exist then inherit from the new format
                $format = $this->options['format'];
                // If the new format is automatic, then use the default 404 image format (otherwise jpg)
                if ($format === 'auto') {
                    $format = Settings::get('image_not_found_format', 'auto');
                    // And lastly, if you have nothing defined you can get a JPG
                    if ($format === 'auto') {
                        $format = 'jpg';
                    }
                }
            }
        }

        // For the most part, the mime is the format: image/{format}
        $mime = $format;

        // Determine mime/fprmat from format
        switch ($format) {
            case 'jpeg':
            case 'jpg':
                $format = 'jpg';
                $mime = 'jpeg';
                break;
            case 'png':
                break;
            case 'webp':
                break;
            case 'bmp':
                break;
            case 'gif':
                break;
            case 'ico':
                break;
        }

        // Cache and return
        return $this->formatCache[$useNewFormat] = [$mime, $format];
    }

    /**
     * Detect if the original image had an alpha channel - used for when flattening an alpha channel
     * image (such as png) into a flat image (such as jpg)
     *
     * @return bool
     */
    private function detectAlpha(): bool
    {
        // Get source file's format
        list($mime, $format) = $this->detectFormat();

        switch ($format) {
            case 'png':
                // Determine if png had alpha channel
                return (ord(@file_get_contents($this->image, null, null, 25, 1)) === 6);
                break;
            default:
                // otherwise false
                return false;
        }
    }

    /**
     * Modify the image with the options provided
     *
     * @return $this
     */
    public function modify()
    {
        // Initialise the resouce if not already initialised
        $this->initResource();

        // available modifiers
        $availableOptions = [
            'blur' => [
                'rules' => 'integer|min:0|max:100',
                'pass' => true
            ],
            'sharpen' => [
                'rules' => 'integer|min:0|max:100',
                'pass' => true
            ],
            'brightness' => [
                'rules' => 'integer|min:-100|max:100',
                'pass' => true
            ],
            'contrast' => [
                'rules' => 'integer|min:-100|max:100',
                'pass' => true
            ],
            'pixelate' => [
                'rules' => 'integer|min:1|max:1000',
                'pass' => true
            ],
            'greyscale' => [
                'rules' => 'accepted',
                'pass' => false
            ],
            'invert' => [
                'rules' => 'accepted',
                'pass' => false
            ],
            'opacity' => [
                'rules' => 'integer|min:0|max:100',
                'pass' => true
            ],
            'rotate' => [
                'rules' => 'integer|min:0|max:360',
                'pass' => true
            ],
            'flip' => [
                'rules' => 'in:v,h',
                'pass' => true
            ],
            'background' => [
                'rules' => 'regex:/^#([a-f0-9]{3}){1,2}$/',
                'pass' => false
            ],
            'colorize' => [
                'rules' => [
                    'regex:/^(?:-?(?:100|[0-9]{2})),(?:-?(?:100|[0-9]{2})),(?:-?(?:100|[0-9]{2}))$/'
                ],
                'pass' => false
            ],
            'insert' => [
                'rules' => 'regex:/^(.+),(\d+),(\d+)$/',
                'pass' => false,
            ]
        ];

        // Compile rules and data of those options that are modifiers
        $data = [];
        $rules = [];
        foreach ($this->options as $key => $value) {
            if (array_key_exists($key, $availableOptions)) {
                $data[$key] = $value;
                $rules[$key] = $availableOptions[$key]['rules'];
            }
        }

        // No modifiers? May as well skip the validation process then
        if (empty($data)) {
            return;
        }

        // Get validator
        $validator = Validator::make($data, $rules);

        // Validate the data
        if (!$validator->passes()) {
            // Errors were found in the options so throw an error with all errors compiled
            $error = [];
            foreach ($validator->messages()->toArray() as $field => $errors) {
                $error[] = implode("\n", $errors);
            }

            throw new Exception('Cannot process image: ' . implode("\n", $error));
        }

        // Passed validation, so begin modifying the image
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'background':
                    $this->im = Image::canvas($this->im->width(), $this->im->height(), $value)->insert($this->im);
                    break;
                case 'colorize':
                    list($r, $g, $b) = explode(',', $value);
                    $this->im->colorize($r, $g, $b);
                    break;
                case 'insert':
                    $exp = explode(',', $value);
                    $path = $exp[0];
                    $position = (isset($exp[1])) ? $exp[1] : null;
                    $x = (isset($exp[2])) ? $exp[2] : null;
                    $y = (isset($exp[3])) ? $exp[3] : null;
                    $this->im->insert($path, $position, $x, $y);
                    break;
                default:
                    // Pass argument if configured to do so:
                    if ($availableOptions[$key]['pass']) {
                        $this->im->{$key}($value);
                    } else {
                        $this->im->{$key}();
                    }
                    break;
            }
        }

        return $this;
    }

    /**
     * Render the image (from the filesystem) in the desired output format, exiting immediately after.
     *
     * @return void
     */
    public function render(): void
    {
        list($mime, $format) = $this->detectFormat(true);

        header('Content-Type: image/' . $mime);
        echo file_get_contents($this->getAbsolutePath());
        exit();
    }

    /**
     * Delete all cached images.
     *
     * @param Carbon|null $minAge Optional minimum age (delete before this date), `null` for all files.
     * @param string|null $directory Optional directory to delete from
     * @return int Number of files deleted
     */
    public static function clearFiles(Carbon $minAge = null, string $directory = null): int
    {
        $directory = $directory ?? Settings::getBasePath();

        $files = collect(File::allFiles($directory))
            ->transform(function ($file) use ($minAge) {
                $delete = true;

                // If a custom time was given, only delete if the file is older
                if ($minAge !== null) {
                    $mtime = Carbon::createFromTimestamp($file->getMTime());
                    $delete = $mtime->lte($minAge);
                }

                return ($delete) ? $file->getRealPath() : null;
            })
            ->filter()
            ->toArray();

        // Iterate each directory and delete it if it's empty
        collect(File::directories($directory))
            ->each(function ($dir) {
                $files = File::allFiles($dir);

                if (empty($files)) {
                    File::deleteDirectory($dir);
                    @unlink($dir);
                }
            });

        // Fire event to hook into and modify $files before deleting
        Event::fire('abweb.imageresize.clearFiles', [&$files, $minAge]);

        // Delete the files
        File::delete($files);

        return count($files);
    }

    /**
     * Parse a given HTML string for images and replace them with the resized copies as per the given modifications.
     *
     * CAUTION: Experimental
     *
     * This uses regex to find and replace HTML content which is often frowned upon. You may supply your own custom
     * regexes, or you may rely on the defaults (which may change in future versions of this plugin soo beware).
     *
     * By default this will search img elements with a src, data-src or lazy-src attribute, as well as any "style"
     * attribute with a background or background-image CSS rule (of which contains a "url()" to an image)
     *
     * Example Usage (a richeditor field that contains custom embedded images that require OTF optimisation or resizing):
     *      {{ service.description | filterHtmlImageResize(600, 600, { mode: 'contain' }) }}
     *      {{ service.description | filterHtmlImageModifiy({ quality: 60 }) }}
     *
     * @param string $html The HTML to find/replace images
     * @param int|null $width
     * @param int|null $height
     * @param array $options
     * @param array $regexes List of regexes (keys) and callbacks (values) to use in the preg_replace_callback.
     * @param int $limit See preg_replace_callback $limit docs
     * @return string The same HTML but with images replaced with their resized URL equivalents
     */
    public static function parseFindReplaceImages(
        string $html,
        int $width = null,
        int $height = null,
        array $options = [],
        array $regexes = null,
        int $limit = 255
    ): string {
        $regexes = ($regexes !== null) ? $regexes : [
            '/(<img [^>]*(?:src|data-src|lazy-src)=)"([^"]+)"/' => function ($match) use ($width, $height, $options) {
                $resizer = new Resizer((string) $match[2]);

                $url = $resizer->resize($width, $height, $options);

                return $match[1] . '"' . $url . '"';
            },
            '/(style="([^"]*)background(-image)?:(\s*[^"]+)?url\(\'?)(.+?)(\'?\))"/' => function ($match) use ($width, $height, $options) {
                $resizer = new Resizer((string) $match[2]);

                $url = $resizer->resize($width, $height, $options);

                return $match[1] . $url . $match[6];
            },
        ];

        foreach ($regexes as $regex => $callback) {
            $html = preg_replace_callback($regex, $callback, $html, $limit);
        }

        return $html;
    }
}
