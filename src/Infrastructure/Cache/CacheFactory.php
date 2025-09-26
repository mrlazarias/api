<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

final class CacheFactory
{
    public static function create(): CacheManager|FileCacheManager
    {
        // Try to create Redis cache first
        try {
            if (extension_loaded('redis')) {
                $redis = new \Predis\Client([
                    'scheme' => 'tcp',
                    'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
                    'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
                    'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                    'database' => (int) ($_ENV['REDIS_DB'] ?? 0),
                ]);
                
                // Test connection
                $redis->ping();
                
                return new CacheManager();
            }
        } catch (\Exception $e) {
            // Redis not available, fall back to file cache
            error_log("Redis not available, using file cache: " . $e->getMessage());
        }
        
        return new FileCacheManager();
    }
}
