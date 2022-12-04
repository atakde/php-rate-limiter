<?php

namespace Atakde\RateLimiter\Storage;

class RedisStorage implements StorageInterface
{
    private $redis;

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    public function get(string $key)
    {
        return $this->redis->get($key);
    }

    public function set(string $key, $value, int $ttl)
    {
        $this->redis->set($key, $value, $ttl);
    }

    public function getExpirationTime(string $key): int
    {
        return $this->redis->ttl($key);
    }
}
