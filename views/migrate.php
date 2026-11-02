<?php

/**
 * Applies pending phinx migrations: GET /migrate?run=1
 *
 * Public on purpose: the deploy doesn't run composer scripts, so this is how migrations reach production.
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

require_once dirname(__DIR__) . '/vendor/autoload.php';

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
