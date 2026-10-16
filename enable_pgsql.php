<?php

$iniPath = 'C:/php-8.4.11/php.ini';
$extDir = 'C:/php-8.4.11/ext/';

if (!file_exists($iniPath)) {
    echo "Error: php.ini not found at $iniPath\n";
    exit(1);
}

// Check for DLLs
$dlls = ['php_pdo_pgsql.dll', 'php_pgsql.dll'];
foreach ($dlls as $dll) {
    if (!file_exists($extDir . $dll)) {
        echo "Warning: $dll not found in $extDir. You might need to download it.\n";
    } else {
        echo "Confirmed $dll exists.\n";
    }
}

$iniContent = file_get_contents($iniPath);

// Enable pdo_pgsql
$iniContent = preg_replace('/^;extension=pdo_pgsql/m', 'extension=pdo_pgsql', $iniContent, -1, $countPdo);
echo "Enabled pdo_pgsql: " . ($countPdo > 0 ? "Yes" : "Already enabled or not found") . "\n";

// Enable pgsql
$iniContent = preg_replace('/^;extension=pgsql/m', 'extension=pgsql', $iniContent, -1, $countPg);
echo "Enabled pgsql: " . ($countPg > 0 ? "Yes" : "Already enabled or not found") . "\n";

if ($countPdo > 0 || $countPg > 0) {
    file_put_contents($iniPath, $iniContent);
    echo "Updated php.ini successfully.\n";
} else {
    echo "No changes made to php.ini (maybe already enabled?).\n";
}
