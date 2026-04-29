<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tx = \App\Models\KartuStok::all();
foreach ($tx as $t) {
    echo $t->tanggal->format('Y-m') . " | ";
}
echo "\n";
