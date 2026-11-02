<?php

/**
 * Applies pending phinx migrations: GET /migrate?run=1
 *
 * Public on purpose: migrations don't reach production otherwise (composer scripts don't seem to run on deploy).
 * It can only apply migrations that are already committed, and applied ones are skipped, so repeated calls are harmless.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

if (($_GET['run'] ?? null) !== '1') {
    http_response_code(400);
    echo "Pass ?run=1 to apply pending migrations\n";
    exit;
}

// phinx lives in vendor-bin (composer bin plugin) to keep its dependencies apart from vimeo/psalm's
$phinx_autoload = dirname(__DIR__) . '/vendor-bin/phinx/vendor/autoload.php';

if (!is_file($phinx_autoload)) {
    http_response_code(500);
    echo "phinx is not installed: run `composer bin all install` on the server\n";
    exit;
}

require_once $phinx_autoload;

/** @var array{dsn: string, user: string, password: string} $db_config */
$db_config = require dirname(__DIR__) . '/dbconfig.php';

// MySQL DDL isn't transactional, so two simultaneous runs could both try to apply the same migration
try {
    $lock_pdo = new PDO($db_config['dsn'], $db_config['user'], $db_config['password']);
    $locked = (int) $lock_pdo->query("SELECT GET_LOCK('psalm_dev_migrate', 0)")->fetchColumn() === 1;
} catch (PDOException $e) {
    error_log('migrate: ' . $e->getMessage());
    http_response_code(500);
    echo "Cannot connect to the database (error {$e->getCode()})\n";
    exit;
}

if (!$locked) {
    http_response_code(409);
    echo "Another migration run is in progress, try again in a minute\n";
    exit;
}

$wrapper = new \Phinx\Wrapper\TextWrapper(
    new \Phinx\Console\PhinxApplication(),
    ['configuration' => dirname(__DIR__) . '/phinx.php']
);

$output = $wrapper->getMigrate('production');
$exit_code = $wrapper->getExitCode();

if ($exit_code !== 0) {
    error_log('migrate: ' . $output);
    http_response_code(500);
    echo "Migration failed (exit code $exit_code), see the server error log\n";
    exit;
}

// only the per-migration lines, e.g. " == 20261102180000 AddArrayOffsetsAndUnusedSuppressSettings: migrated 0.0123s"
preg_match_all('/^\s*== .+$/m', $output, $matches);

echo $matches[0] ? implode("\n", array_map('trim', $matches[0])) . "\n" : "No pending migrations\n";
