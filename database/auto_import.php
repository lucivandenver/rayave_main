<?php
/**
 * Automatically imports database/init/01-flowershop.sql on first boot
 * when deploying to a fresh empty MySQL database (such as Railway MySQL).
 * Safe to run on every container startup: skips if tables already exist.
 */

if (PHP_SAPI !== 'cli') {
    exit(0);
}

$sqlFile = __DIR__ . '/init/01-flowershop.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "[auto_import] SQL seed file not found, skipping.\n");
    exit(0);
}

// Resolve DB credentials (Railway MYSQL_* / MYSQL_URL, Docker Compose, or DB_*)
$urlHost = $urlPort = $urlUser = $urlPass = $urlName = null;
$rawUrl = getenv('MYSQL_URL') ?: (getenv('DATABASE_URL') ?: '');
if ($rawUrl !== '') {
    $p = @parse_url($rawUrl);
    if (is_array($p)) {
        $urlHost = $p['host'] ?? null;
        $urlPort = isset($p['port']) ? (string) $p['port'] : null;
        $urlUser = isset($p['user']) ? rawurldecode($p['user']) : null;
        $urlPass = isset($p['pass']) ? rawurldecode($p['pass']) : null;
        $urlName = isset($p['path']) ? ltrim($p['path'], '/') : null;
    }
}

$dbHost = getenv('DOCKER_DB_HOST') ?: (getenv('MYSQLHOST') ?: ($urlHost ?: (getenv('DB_HOST') ?: '')));
if (getenv('DOCKER_ENV') === 'true' && ($dbHost === '' || $dbHost === 'localhost' || $dbHost === '127.0.0.1')) {
    $dbHost = 'db';
}
$dbPort = getenv('DOCKER_DB_PORT') ?: (getenv('MYSQLPORT') ?: ($urlPort ?: (getenv('DB_PORT') ?: '3306')));
$dbUser = getenv('DOCKER_DB_USER') ?: (getenv('MYSQLUSER') ?: ($urlUser ?: (getenv('DB_USER') ?: 'root')));
$dbName = getenv('DOCKER_DB_NAME') ?: (getenv('MYSQLDATABASE') ?: ($urlName ?: (getenv('DB_NAME') ?: 'flowershop')));

$dbPass = '';
if (getenv('DOCKER_DB_PASS') !== false) {
    $dbPass = (string) getenv('DOCKER_DB_PASS');
} elseif (getenv('MYSQLPASSWORD') !== false) {
    $dbPass = (string) getenv('MYSQLPASSWORD');
} elseif ($urlPass !== null) {
    $dbPass = (string) $urlPass;
} elseif (getenv('DB_PASS') !== false) {
    $dbPass = (string) getenv('DB_PASS');
}

if ($dbHost === '') {
    fwrite(STDOUT, "[auto_import] No database host configured yet, skipping.\n");
    exit(0);
}

// Wait briefly for MySQL to accept connections (useful on cold starts)
$pdo = null;
$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
for ($attempt = 1; $attempt <= 6; $attempt++) {
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        break;
    } catch (Throwable $e) {
        fwrite(STDOUT, "[auto_import] Waiting for MySQL ({$attempt}/6): " . $e->getMessage() . "\n");
        sleep(2);
    }
}

if (!$pdo) {
    fwrite(STDERR, "[auto_import] Could not connect to MySQL on boot, continuing startup anyway.\n");
    exit(0);
}

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($stmt && $stmt->fetch()) {
        fwrite(STDOUT, "[auto_import] Database '{$dbName}' already initialized, skipping import.\n");
        exit(0);
    }
} catch (Throwable $e) {
    fwrite(STDERR, "[auto_import] Table check failed: " . $e->getMessage() . "\n");
    exit(0);
}

fwrite(STDOUT, "[auto_import] Fresh database '{$dbName}' detected! Importing 01-flowershop.sql...\n");

$env = array_merge($_ENV, $_SERVER);
$env['MYSQL_PWD'] = $dbPass;

$runMysqlImport = function (string $extraFlags = '') use ($dbHost, $dbPort, $dbUser, $dbName, $sqlFile, $env): array {
    $cmd = sprintf(
        'mysql %s --host=%s --port=%s --user=%s --default-character-set=utf8mb4 %s < %s 2>&1',
        $extraFlags,
        escapeshellarg($dbHost),
        escapeshellarg($dbPort),
        escapeshellarg($dbUser),
        escapeshellarg($dbName),
        escapeshellarg($sqlFile)
    );

    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $proc = proc_open($cmd, $descriptors, $pipes, __DIR__, $env);
    if (!is_resource($proc)) {
        return ['code' => 1, 'output' => 'Unable to launch mysql client.'];
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($proc);

    return ['code' => $exitCode, 'output' => trim($stdout . ' ' . $stderr)];
};

$result = $runMysqlImport('');
if ($result['code'] !== 0) {
    fwrite(STDOUT, "[auto_import] Standard import failed ({$result['output']}), retrying with --skip-ssl...\n");
    $result = $runMysqlImport('--skip-ssl');
}

if ($result['code'] === 0) {
    fwrite(STDOUT, "[auto_import] Successfully imported database seed into '{$dbName}'!\n");
} else {
    fwrite(STDERR, "[auto_import] mysql import exited with code {$result['code']}: {$result['output']}\n");
}
exit(0);
