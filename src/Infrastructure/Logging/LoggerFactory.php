<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public static function create(string $name = 'app'): LoggerInterface
    {
        $logger = new Logger($name);
        
        $logLevel = strtoupper($_ENV['LOG_LEVEL'] ?? 'INFO');
        $logPath = $_ENV['LOG_PATH'] ?? 'storage/logs';
        
        // Ensure log directory exists
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        
        // Add handlers
        if ($_ENV['APP_ENV'] === 'production') {
            $handler = new RotatingFileHandler(
                "{$logPath}/app.log",
                0, // Keep all files
                constant("Monolog\Logger::{$logLevel}")
            );
        } else {
            $handler = new StreamHandler(
                'php://stdout',
                constant("Monolog\Logger::{$logLevel}")
            );
        }
        
        $logger->pushHandler($handler);
        
        // Add processors for additional context
        $logger->pushProcessor(new IntrospectionProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());
        $logger->pushProcessor(new WebProcessor());
        
        return $logger;
    }
}

