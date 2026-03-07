<?php
$path = '/var/www/dramabox/.env';
$env = file_get_contents($path);
$env = preg_replace('/^DB_PASSWORD=.*/m', 'DB_PASSWORD="MciarA@6263M"', $env);
file_put_contents($path, $env);
echo "DB_PASSWORD fixed!\n";
// Show the result
preg_match('/^DB_PASSWORD=.*/m', $env, $m);
echo $m[0] . "\n";
