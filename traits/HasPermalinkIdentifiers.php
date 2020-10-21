<?php

namespace ABWebDevelopers\ImageResize\Traits;

trait HasPermalinkIdentifiers
{
    /**
     * Get the identifier for the permalink
     *
     * @param string $key
     * @return string
     */
    public function getPermalinkIdentifier(string $key = null): string
    {
        if ($key !== null) {
            $key .= '/';
        }

        return $key . strtolower(basename(str_replace('\\', '/', static::class))) . '/' . $this->slug;
    }
}
