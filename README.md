# php-rate-limiter

A PHP package for rate limiting.

## Installation

Install via composer

```bash 
composer require atakde/php-rate-limiter
```

## Usage

```php

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$storage = new RedisStorage($redis);

// Allow maximum 10 requests in 60 seconds.
$rateLimitter = new RateLimiter([
    'refillPeriod' => 60,
    'maxCapacity' => 10,
    'prefix' => 'api'
], $storage);

$ip = $_SERVER['REMOTE_ADDR'];
if ($rateLimitter->check($ip)) {
    echo 'OK';
} else {
    echo 'Limit Exceeded';
}


```
