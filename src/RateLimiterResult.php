<?php

declare(strict_types=1);

namespace Antikirra;

class RateLimiterResult
{
    private bool $passed;
    private int $count;

    public function __construct(bool $passed, int $count)
    {
        $this->passed = $passed;
        $this->count = $count;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function isFailed(): bool
    {
        return !$this->passed;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
