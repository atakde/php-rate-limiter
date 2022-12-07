<?php

namespace Atakde\RateLimiter\Storage;

interface StorageInterface
{
    public function get(string $key);
    public function set(string $key, $value, int $ttl);
    public function delete(string $key);
}
