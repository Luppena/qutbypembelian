<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$toSkip = [
    '2025_12_13_024817_rename_total_bruto_to_pajak_persen_on_penjualan_table',
];

foreach ($toSkip as $migration) {
    $exists = DB::table('migrations')->where('migration', $migration)->exists();
    if (!$exists) {
        DB::table('migrations')->insert(['migration' => $migration, 'batch' => 1022]);
        echo "Inserted: $migration\n";
    } else {
        echo "Already exists: $migration\n";
    }
}

echo "Done!\n";
