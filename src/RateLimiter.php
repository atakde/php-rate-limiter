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
    private int $maxCapacity;
    private int $refillPeriod;
    private StorageInterface $storage;
    private array $headers = [
        'X-RateLimit-Limit' => '{{maxCapacity}}',
        'X-RateLimit-Remaining' => '{{currentAmmount}}',
        'X-RateLimit-Reset' => '{{reset}}',
    ];

    public function __construct(array $options, StorageInterface $storage)
    {
        $this->prefix = $options['prefix'];
        $this->maxCapacity = $options['maxCapacity'];
        $this->refillPeriod = $options['refillPeriod'];
        $this->headers = $options['headers'] ?? $this->headers;
        $this->storage = $storage;
    }

    public function check(string $identifier): bool
    {
        $key = $this->prefix . $identifier;
        // if the bucket does not exist, create it
        if (!$this->hasBucket($key)) {
            $this->createBucket($key);
        }

        $currentTime = time();
        $lastCheck = $this->storage->get($key . 'last_check');
        $tokensToAdd = ($currentTime - $lastCheck) * ($this->maxCapacity / $this->refillPeriod);
        $currentAmmount = $this->storage->get($key);
        // optimization of adding a token every rate ÷ per seconds
        $bucket = $currentAmmount + $tokensToAdd;
        // if is greater than max ammount, set it to max ammount
        $bucket = $bucket > $this->maxCapacity ? $this->maxCapacity : $bucket;
        // set last check time
        $this->storage->set($key . 'last_check', $currentTime, $this->refillPeriod);

        if ($bucket < 1) {
            return false;
        }

        $this->storage->set($key, $bucket - 1, $this->refillPeriod);
        return true;
    }

    private function createBucket(string $key)
    {
        $this->storage->set($key . 'last_check', time(), $this->refillPeriod);
        $this->storage->set($key, $this->maxCapacity - 1, $this->refillPeriod);
    }

    private function hasBucket(string $key): bool
    {
        return $this->storage->get($key) !== null;
    }

    public function get(string $identifier): int
    {
        $key = $this->prefix . $identifier;
        return $this->storage->get($key);
    }

    public function delete(string $identifier): void
    {
        $key = $this->prefix . $identifier;
        $this->storage->delete($key);
    }

    public function headers(string $identifier): array
    {
        $key = $this->prefix . $identifier;
        $lastCheck = $this->storage->get($key . 'last_check');
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[$key] = str_replace('{{maxCapacity}}', $this->maxCapacity, $value);
            $headers[$key] = str_replace('{{currentAmmount}}', $this->get($identifier), $headers[$key]);
            $headers[$key] = str_replace('{{reset}}', $lastCheck + $this->refillPeriod, $headers[$key]);
        }

        return $headers;
    }
}
