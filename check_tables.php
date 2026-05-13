<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tables = DB::select('SHOW TABLES');
foreach ($tables as $row) {
    $name = array_values((array)$row)[0];
    if (str_contains($name, 'daftar') || str_contains($name, 'akun')) {
        echo $name . PHP_EOL;
    }
}
