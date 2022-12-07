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
    private int $maxAmmount;
    private int $refillTime;
    private StorageInterface $storage;

    public function __construct(array $options, StorageInterface $storage)
    {
        $this->prefix = $options['prefix'];
        $this->maxAmmount = $options['maxAmmount'];
        $this->refillTime = $options['refillTime'];
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
        $tokensToAdd = ($currentTime - $lastCheck) * ($this->maxAmmount / $this->refillTime);
        $currentAmmount = $this->storage->get($key);
        // optimization of adding a token every rate ÷ per seconds
        $bucket = $currentAmmount + $tokensToAdd;
        // if is greater than max ammount, set it to max ammount
        $bucket = $bucket > $this->maxAmmount ? $this->maxAmmount : $bucket;
        // set last check time
        $this->storage->set($key . 'last_check', $currentTime, $this->refillTime);

        if ($bucket < 1) {
            return false;
        }

        $this->storage->set($key, $bucket - 1, $this->refillTime);
        return true;
    }

    private function createBucket(string $key)
    {
        $this->storage->set($key . 'last_check', time(), $this->refillTime);
        $this->storage->set($key, $this->maxAmmount - 1, $this->refillTime);
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
}
