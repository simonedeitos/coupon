<?php
declare(strict_types=1);

$envFile = __DIR__ . '/.env';

echo '<pre>';
echo "File .env trovato: " . (is_file($envFile) ? 'SÌ' : 'NO — path cercato: ' . $envFile) . "\n\n";

if (is_file($envFile)) {
    $raw = file_get_contents($envFile);
    echo "Contiene BOM UTF-8: " . (substr($raw, 0, 3) === "\xEF\xBB\xBF" ? 'SÌ (PROBLEMA!)' : 'NO') . "\n";
    echo "Numero di righe: " . count(file($envFile)) . "\n\n";

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        if (!isset($_SERVER[$k]) && !isset($_ENV[$k])) {
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
        }
    }
}

$dbHost = getenv('DB_HOST') ?: '(non impostato)';
$dbName = getenv('DB_NAME') ?: '(non impostato)';
$dbUser = getenv('DB_USER') ?: '(non impostato)';
$dbPass = getenv('DB_PASS') ?: '';

echo "DB_HOST = '" . $dbHost . "'\n";
echo "DB_NAME = '" . $dbName . "'\n";
echo "DB_USER = '" . $dbUser . "'\n";
echo "DB_PASS length = " . strlen($dbPass) . " caratteri\n";
echo "DB_PASS primo carattere = '" . ($dbPass !== '' ? $dbPass[0] : '') . "'\n";
echo "DB_PASS ultimo carattere = '" . ($dbPass !== '' ? $dbPass[strlen($dbPass) - 1] : '') . "'\n";
echo "DB_PASS contiene virgolette = " . (strpos($dbPass, '"') !== false || strpos($dbPass, "'") !== false ? 'SÌ (PROBLEMA!)' : 'NO') . "\n";
echo "DB_PASS (esadecimale, per vedere caratteri invisibili):\n" . bin2hex($dbPass) . "\n";
echo '</pre>';