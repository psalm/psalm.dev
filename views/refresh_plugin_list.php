<?php

// Temporary: rebuilds the plugin list once after #129 changed the list.json format. Remove after use.
// Public on purpose: it only works for 10 minutes after deploy (this file's mtime), does nothing once
// the list is readable, and never runs twice at once.

use PsalmDotOrg\PluginRepository;

require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

if (time() - filemtime(__FILE__) > 10 * 60) {
    http_response_code(410);
    echo "This endpoint has expired.\n";
    exit;
}

if (PluginRepository::getCatalog()->plugins !== []) {
    echo "Plugin list is already up to date, nothing to do.\n";
    exit;
}

$lock = fopen(sys_get_temp_dir() . '/psalm-dev-refresh-plugin-list.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    http_response_code(409);
    echo "A refresh is already running, try again in a minute.\n";
    exit;
}

set_time_limit(300);

try {
    PluginRepository::updateList();
} catch (RuntimeException $e) {
    http_response_code(500);
    echo 'Refresh failed: ' . $e->getMessage() . "\n";
    exit;
} finally {
    flock($lock, LOCK_UN);
}

echo 'Plugin list refreshed: ' . count(PluginRepository::getCatalog()->plugins) . " plugins.\n";
