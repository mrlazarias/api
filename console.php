<?php

declare(strict_types=1);

use App\Infrastructure\Console\ConsoleApplication;
use Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// Create and run console application
$console = new ConsoleApplication();
$console->run();
