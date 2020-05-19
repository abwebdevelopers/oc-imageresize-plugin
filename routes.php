<?php

use ABWebDevelopers\ImageResize\Classes\Resizer;
use Illuminate\Support\Facades\Cache;

/**
 * Publicly accessible URL for resizing an image (lazy-resize), using a cache hash/ext.
 */
Route::get('imageresize/{hash}.{ext}', function (string $hash, string $ext) {
    $config = Cache::get(Resizer::CACHE_PREFIX . $hash);

    if (empty($config)) {
        $config = [
            'image' => null,
            'options' => [],
            'formatCache' => [],
        ];
    }

    return Resizer::using($config['image'])
        ->setHash($hash)
        ->setOptions($config['options'] ?? [])
        ->setFormatCache($config['formatCache'] ?? [])
        ->doResize()
        ->render();
});
