<?php
// Simulate what config:cache does
chdir('/var/www/dramabox');

// Remove cached config first
@unlink('bootstrap/cache/config.php');

// Boot the app fresh
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Now get the config
$config = $app['config']->all();
$dbPass = $config['database']['connections']['mysql']['password'] ?? 'NOT_SET';
$appKey = $config['app']['key'] ?? 'NOT_SET';
echo "Config DB password: [{$dbPass}]\n";
echo "Config APP key: [{$appKey}]\n";
echo "Direct env() DB_PASSWORD: [" . env('DB_PASSWORD') . "]\n";
echo "Direct env() APP_KEY: [" . env('APP_KEY') . "]\n";
echo "getenv DB_PASSWORD: [" . getenv('DB_PASSWORD') . "]\n";
echo "SERVER DB_PASSWORD: [" . ($_SERVER['DB_PASSWORD'] ?? 'not in SERVER') . "]\n";
echo "ENV DB_PASSWORD: [" . ($_ENV['DB_PASSWORD'] ?? 'not in ENV') . "]\n";
