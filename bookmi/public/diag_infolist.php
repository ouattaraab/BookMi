<?php
// Temporary diagnostic script — DELETE AFTER USE
$file = __DIR__ . '/../app/Filament/Resources/IdentityVerificationResource.php';
$content = file_get_contents($file);
$hasInfolist = strpos($content, 'public static function infolist') !== false;
$mtime = filemtime($file);

header('Content-Type: text/plain');
echo "File mtime: " . date('Y-m-d H:i:s', $mtime) . "\n";
echo "Has infolist() method: " . ($hasInfolist ? 'YES' : 'NO') . "\n";
echo "PHP version: " . PHP_VERSION . "\n";
echo "OPcache enabled: " . (function_exists('opcache_get_status') ? 'yes' : 'no') . "\n";

if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    echo "OPcache running: " . ($status['opcache_enabled'] ? 'yes' : 'no') . "\n";
}

// Now invalidate all modified resource files
$resources = [
    'IdentityVerificationResource',
    'AdminAlertResource',
    'AdminWarningResource',
    'ReviewResource',
    'ActivityLogResource',
];

echo "\nInvalidating resource files:\n";
foreach ($resources as $resource) {
    $path = __DIR__ . '/../app/Filament/Resources/' . $resource . '.php';
    if (function_exists('opcache_invalidate')) {
        $result = opcache_invalidate($path, true);
        echo "  $resource: " . ($result ? 'OK' : 'failed') . "\n";
    } else {
        echo "  opcache_invalidate not available\n";
    }
}

echo "\nDone.";
