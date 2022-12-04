<?php

namespace Atakde\RateLimiter;

use Atakde\RateLimiter\Storage\StorageInterface;

/**
 * Class RateLimiter
 * Token Bucket Algorithm
 * @package Atakde\RateLimiter
 */

class RateLimiter
{
    private string $prefix;
    private int $limit;
    private int $ttl;
    private StorageInterface $storage;

    public function __construct(array $options, StorageInterface $storage)
    {
        $this->prefix = $options['prefix'] ?? 'rate_limiter';
        $this->limit = $options['limit'] ?? 10;
        $this->ttl = $options['ttl'] ?? 60;
        $this->storage = $storage;
    }

    public function check(string $identifier): bool
    {
        $key = $this->prefix . $identifier;
        $current = $this->storage->get($key);

        if (!$current) {
            $this->storage->set($key, 1, $this->ttl);
            return true;
        }

        if ($current < $this->limit) {
            $this->storage->set($key, $current + 1, $this->ttl);
            return true;
        }

        return false;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getStorage()
    {
        return $this->storage;
    }

    public function setStorage($storage): void
    {
        $this->storage = $storage;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function getRemaining(string $identifier): int
    {
        $key = $this->prefix . $identifier;
        $current = $this->storage->get($key);
        return !$current ? $this->limit : $this->limit - $current;
    }

    public function getReset(string $identifier): int
    {
        $key = $this->prefix . $identifier;
        return $this->storage->getExpirationTime($key) ?? 0;
    }

    public function getHeaders(string $identifier): array
    {
        return [
            'X-RateLimit-Limit' => $this->limit,
            'X-RateLimit-Remaining' => $this->getRemaining($identifier),
            'X-RateLimit-Reset' => $this->getReset($identifier)
        ];
    }

    public function purge(string $identifier): void
    {
        $key = $this->prefix . $identifier;
        $this->storage->set($key, 0, 1);
    }
}
