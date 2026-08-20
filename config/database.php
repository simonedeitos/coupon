<?php

declare(strict_types=1);

// Load optional .env file (if present, takes precedence over defaults below)
$_envFile = dirname(__DIR__) . '/.env';
if (is_file($_envFile)) {
    $_raw = file_get_contents($_envFile);
    if ($_raw !== false) {
        if (substr($_raw, 0, 3) === "\xEF\xBB\xBF") {
            $_raw = substr($_raw, 3);
        }
        foreach (preg_split('/\r\n|\r|\n/', $_raw) as $_line) {
            $_line = trim($_line);
            if ($_line === '' || $_line[0] === '#' || strpos($_line, '=') === false) {
                continue;
            }
            $_parts = explode('=', $_line, 2);
            $_k = trim($_parts[0]);
            $_v = trim($_parts[1] ?? '');
            if (strlen($_v) >= 2) {
                $_first = $_v[0];
                $_last = $_v[strlen($_v) - 1];
                if (($_first === '"' && $_last === '"') || ($_first === "'" && $_last === "'")) {
                    $_v = substr($_v, 1, -1);
                }
            }
            if ($_k !== '' && !isset($_SERVER[$_k]) && !isset($_ENV[$_k])) {
                putenv("{$_k}={$_v}");
                $_ENV[$_k] = $_v;
            }
        }
    }
    unset($_envFile, $_raw, $_line, $_parts, $_k, $_v, $_first, $_last);
}

return [
    'driver' => 'mysql',
    'host' => getenv('DB_HOST') ?: 'localhost',
    'host_fallback' => getenv('DB_HOST_FALLBACK') ?: '127.0.0.1',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME') ?: '',
    'username' => getenv('DB_USER') ?: '',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];