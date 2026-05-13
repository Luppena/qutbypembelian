<?php
$files = [
    'app/Filament/Widgets/BarangMasukHariIni.php',
    'app/Filament/Widgets/GudangStatsOverview.php',
    'app/Filament/Widgets/StokMinimumAlert.php'
];
$canViewStr = "
    public static function canView(): bool
    {
        \$userRole = auth()->user()->role;
        \$userRole = \$userRole instanceof \App\Enums\UserRole ? \$userRole->value : (string) \$userRole;
        return in_array(\$userRole, ['admin', 'operasional', 'gudang']);
    }
";

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'public static function canView') === false) {
        $content = preg_replace('/class [a-zA-Z]+ extends BaseWidget\s*\{/', "$0$canViewStr", $content);
        file_put_contents($file, $content);
        echo 'Updated: ' . $file . PHP_EOL;
    }
}
