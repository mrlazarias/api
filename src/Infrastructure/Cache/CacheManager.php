<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use Predis\Client;

final class CacheManager
{
    private Client $redis;

    public function __construct()
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
            'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'database' => (int) ($_ENV['REDIS_DB'] ?? 0),
        ]);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);
        
        if ($value === null) {
            return $default;
        }
        
        $decoded = json_decode($value, true);
        return $decoded === null ? $value : $decoded;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $encodedValue = is_string($value) ? $value : json_encode($value);
        
        if ($ttl !== null) {
            return (bool) $this->redis->setex($key, $ttl, $encodedValue);
        }
        
        return (bool) $this->redis->set($key, $encodedValue);
    }

    public function delete(string $key): bool
    {
        return (bool) $this->redis->del($key);
    }

    public function exists(string $key): bool
    {
        return (bool) $this->redis->exists($key);
    }

    public function increment(string $key, int $value = 1): int
    {
        return $this->redis->incrby($key, $value);
    }

    public function decrement(string $key, int $value = 1): int
    {
        return $this->redis->decrby($key, $value);
    }

    public function flush(): bool
    {
        return (bool) $this->redis->flushdb();
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if ($this->exists($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
}

