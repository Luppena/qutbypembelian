<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$users = User::select('id','name','email','role')->get();
foreach ($users as $u) {
    echo "id={$u->id} | name={$u->name} | email={$u->email} | role={$u->role}" . PHP_EOL;
}
