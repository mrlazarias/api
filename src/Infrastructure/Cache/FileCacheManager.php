<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

/**
 * File-based cache manager as fallback when Redis is not available
 */
final class FileCacheManager
{
    private string $cacheDir;

    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../../../storage/cache';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $content = file_get_contents($file);
        if ($content === false) {
            return $default;
        }
        
        $data = json_decode($content, true);
        if (!$data || !isset($data['expires_at'], $data['value'])) {
            return $default;
        }
        
        // Check if expired
        if ($data['expires_at'] !== null && time() > $data['expires_at']) {
            $this->delete($key);
            return $default;
        }
        
        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $file = $this->getFilePath($key);
        $dir = dirname($file);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $data = [
            'value' => $value,
            'expires_at' => $ttl ? time() + $ttl : null,
            'created_at' => time(),
        ];
        
        return file_put_contents($file, json_encode($data)) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }

    public function exists(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function increment(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $value;
        $this->set($key, $new);
        
        return $new;
    }

    public function decrement(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = max(0, $current - $value);
        $this->set($key, $new);
        
        return $new;
    }

    public function flush(): bool
    {
        $files = glob($this->cacheDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
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

    private function getFilePath(string $key): string
    {
        $hash = md5($key);
        $subDir = substr($hash, 0, 2);
        
        return $this->cacheDir . '/' . $subDir . '/' . $hash . '.cache';
    }
}
