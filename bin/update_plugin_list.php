<?php

require_once __DIR__ . '/../vendor/autoload.php';

$plugin_names_json = file_get_contents("https://packagist.org/packages/list.json?type=psalm-plugin");
if (!$plugin_names_json) {
    echo "Failed to fetch plugins from packagist.org: " . var_export(error_get_last(), true) . PHP_EOL;
    exit(2);
}

$plugin_names = json_decode($plugin_names_json, true);
if (!isset($plugin_names['packageNames']) || !is_array($plugin_names['packageNames'])) {
    echo "Unexpected plugin list format: " . var_export($plugin_names, true) . PHP_EOL;
    exit(3);
}

$plugins = [];
foreach ($plugin_names['packageNames'] as $package_name) {
    $plugin_json = file_get_contents("https://packagist.org/packages/$package_name.json");
    if (!$plugin_json) {
        echo "Failed to get data for $package_name: " . var_export(error_get_last(), true) . ", skipping..." . PHP_EOL;
        continue;
    }
    $plugin = json_decode($plugin_json, true);

    // some basic checks for expected format
    if (
        !isset($plugin['package'])
        || !is_array($plugin['package'])
        || !isset($plugin['package']['name'])
        || !isset($plugin['package']['description'])
    ) {
        echo "Unexpected plugin data format: " . var_export($plugin, true) . ", skipping..." . PHP_EOL;
        continue;
    }
    $plugins[$package_name] = $plugin['package'];
}

$written = file_put_contents(
    __DIR__ . '/../assets/plugins/list.json',
    json_encode($plugins),
    LOCK_EX
);

if (false === $written) {
    echo "Failed to store plugin list: " . var_export(error_get_last(), true) . PHP_EOL;
    exit(4);
}

exit(0);
