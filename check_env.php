<?php
// Check what the env parser reads
require '/var/www/dramabox/vendor/autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable('/var/www/dramabox');
$dotenv->load();

echo "DB_PASSWORD from env: [" . $_ENV['DB_PASSWORD'] . "]\n";
echo "DB_USERNAME from env: [" . $_ENV['DB_USERNAME'] . "]\n";
