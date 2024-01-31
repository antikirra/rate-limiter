# [DRAFT] RateLimiter implementation for PHP

## Install

```console
composer require antikirra/rate-limiter
```

## Basic usage

```php
<?php

use Antikirra\RateLimiter;

require __DIR__ . '/vendor/autoload.php';

$redis = new Redis();
$redis->connect('127.0.0.1');

$limiter = new RateLimiter($redis, "signin_by_ip{$_SERVER['REMOTE_ADDR']}", 3, 60, 0.75);

$result = $limiter->check(); // returns the actual counter value without any side effects
echo $result->getCount(); // (int) actual counter value
print_r($result->isPassed()); // (bool) true - it's okay, false - flood has been detected!!!

$result = $limiter->hit(); // increments the counter value, returning the result of the check
echo $result->getCount(); // (int) updated counter value
print_r($result->isPassed()); // (bool) true - it's okay, false - flood has been detected!!!
print_r($result->isFailed()); // (bool) true - flood has been detected!!!, false - it's okay

$limiter->reset(); // (void) resets counters to zero
```