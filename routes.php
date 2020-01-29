<?php

use ABWebDevelopers\ImageResize\Classes\Resizer;
use Cache;
use Route;

/**
 * Publicly accessible URL for viewing a resized image, using a cache hash.
 */
Route::get('imageresize/{hash}', function (string $hash) {
    $config = Cache::get(Resizer::CACHE_PREFIX . $hash);

    if (empty($config)) {
        $config = [
            'image' => null,
            'options' => [],
        ];
    }

    return Resizer::using($config['image'])
        ->setHash($hash)
        ->setOptions($config['options'])
        ->doResize()
        ->render();
});
