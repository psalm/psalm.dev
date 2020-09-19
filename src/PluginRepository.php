<?php

namespace PsalmDotOrg;

use RuntimeException;

final class PluginRepository
{
    private const PLUGIN_LIST_FILE = __DIR__ . '/../assets/plugins/list.json';
    private const PLUGIN_NAMES_ENDPOINT = "https://packagist.org/packages/list.json?type=psalm-plugin";

    /** @return iterable<Plugin> */
    public static function getAll(): iterable
    {
        $plugins_json = self::readList();
        if (!$plugins_json) {
            error_log("Failed to read plugin list");
            $plugins = [];
        } else {
            $plugins = json_decode($plugins_json, true);
        }
        return array_map([Plugin::class, 'fromRepoEntry'], $plugins);
    }

    private static function readList(): string
    {
        $fp = fopen(self::PLUGIN_LIST_FILE, 'r');

        flock($fp, LOCK_SH);
        $plugins_json = stream_get_contents($fp);
        flock($fp, LOCK_UN);

        return $plugins_json;
    }

    public static function updateList(): void
    {
        $plugin_names_json = file_get_contents(self::PLUGIN_NAMES_ENDPOINT);
        if (!$plugin_names_json) {
            throw new RuntimeException(
                "Failed to fetch plugins from packagist.org: " . var_export(error_get_last(), true),
                2
            );
        }

        $plugin_names = json_decode($plugin_names_json, true);
        if (!isset($plugin_names['packageNames']) || !is_array($plugin_names['packageNames'])) {
            throw new RuntimeException(
                "Unexpected plugin list format: " . var_export($plugin_names, true),
                3
            );
        }

        $plugins = [];
        foreach ($plugin_names['packageNames'] as $package_name) {
            $plugin_json = file_get_contents("https://packagist.org/packages/$package_name.json");
            if (!$plugin_json) {
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
                continue;
            }

            $plugins[$package_name] = $plugin['package'];
        }

        uasort(
            $plugins,
            function (array $a, array $b): int {
                return -($a['downloads']['monthly'] <=> $b['downloads']['monthly']);
            }
        );

        $written = file_put_contents(
            self::PLUGIN_LIST_FILE,
            json_encode($plugins),
            LOCK_EX
        );

        if (false === $written) {
            throw new RuntimeException(
                "Failed to store plugin list: " . var_export(error_get_last(), true),
                4
            );
        }
    }
}
