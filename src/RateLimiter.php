<?php

declare(strict_types=1);

namespace Antikirra;

class RateLimiter
{
    private \Redis $redis;
    private string $key;
    private int $limit;
    private int $interval;
    private float $sliding;

    public function __construct(
        \Redis $redis,
        string $key,
        int    $limit,
        int    $interval,
        float  $sliding = 1.0
    )
    {
        $this->redis = $redis;
        $this->key = $key;
        $this->limit = $limit;
        $this->interval = $interval;
        $this->sliding = $sliding;
    }

    private function getWindowKey(int $suffix): string
    {
        return "{$this->key}_{$this->limit}_{$this->interval}_{$suffix}";
    }

    private function getWindowKeys(): array
    {
        $suffix = (int)(time() / $this->interval);

        return [
            $this->getWindowKey($suffix),
            $this->getWindowKey($suffix + 1),
        ];
    }

    private function internalCheck(int $count): bool
    {
        return $count <= $this->limit;
    }

    public function check(): RateLimiterResult
    {
        [$currentWindowKey, $nextWindowKey] = $this->getWindowKeys();

        [$w1, $w2] = $this->redis
            ->multi()
            ->get($currentWindowKey)
            ->get($nextWindowKey)
            ->exec();

        $count = (int)$w1 + (int)$w2;

        return new RateLimiterResult($this->internalCheck($count), $count);
    }

    public function hit(): RateLimiterResult
    {
        [$currentWindowKey, $nextWindowKey] = $this->getWindowKeys();

        $window = probability($this->sliding) ? $currentWindowKey : $nextWindowKey;

        [, , , , $w1, $w2] = $this->redis
            ->multi()
            ->setnx($currentWindowKey, '0')
            ->expire($currentWindowKey, $this->interval, 'NX')
            ->setnx($nextWindowKey, '0')
            ->expire($nextWindowKey, $this->interval * 2, 'NX')
            ->incrBy($currentWindowKey, $currentWindowKey === $window ? 1 : 0)
            ->incrBy($nextWindowKey, $nextWindowKey === $window ? 1 : 0)
            ->exec();

        $count = (int)$w1 + (int)$w2;

        return new RateLimiterResult($this->internalCheck($count), $count);
    }

    public function reset(): void
    {
        [$currentWindowKey, $nextWindowKey] = $this->getWindowKeys();
        $this->redis->del($currentWindowKey, $nextWindowKey);
    }
}
