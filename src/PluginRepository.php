<?php

namespace PsalmDotOrg;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\VersionParser;
use RuntimeException;
use UnexpectedValueException;

final class PluginRepository
{
    private const PLUGIN_LIST_FILE = __DIR__ . '/../assets/plugins/list.json';
    private const PLUGIN_NAMES_ENDPOINT = 'https://packagist.org/packages/list.json?type=psalm-plugin';
    private const PSALM_RELEASES_ENDPOINT = 'https://repo.packagist.org/p2/vimeo/psalm.json';
    private const HTTP_TIMEOUT_SECONDS = 20;
    // Packagist asks API clients to identify themselves with a way to contact them: https://packagist.org/apidoc
    private const HTTP_USER_AGENT = 'psalm.dev plugin list (+https://github.com/psalm/psalm.dev/issues)';

    // Unlisted at their author's request after they unintentionally skewed the plugin ranking (#101)
    private const EXCLUDED_PACKAGES = [
        'ghostwriter/example-psalm-plugin',
        'ghostwriter/phpunit-psalm-plugin',
        'ghostwriter/psalm-plugin',
    ];

    /**
     * Mirrors Psalm's Config::FIRST_PARTY_PLUGINS (private there): libraries that make Psalm enable a bundled plugin.
     * Bundled plugins missing here are still listed as bundled, just without the library hint.
     */
    private const BUNDLED_PLUGIN_LIBRARIES = [
        'psalm/plugin-phpunit' => ['phpunit/phpunit'],
        'psalm/plugin-mockery' => ['mockery/mockery'],
        'psalm/plugin-symfony' => ['symfony/framework-bundle'],
        'psalm/plugin-laravel' => ['laravel/framework', 'orchestra/testbench'],
        'psalm/plugin-sabre-event' => ['sabre/event'],
    ];

    public static function getCatalog(): PluginCatalog
    {
        $data = json_decode(self::readList(), true);

        // Also rejects the pre-catalog format (a plain array of Packagist entries) until the next update.
        if (!is_array($data) || !isset($data['plugins'])) {
            error_log('Failed to read plugin list');
            return PluginCatalog::empty();
        }

        return PluginCatalog::fromArray($data);
    }

    private static function readList(): string
    {
        $fp = @fopen(self::PLUGIN_LIST_FILE, 'r');
        if ($fp === false) {
            return '';
        }

        flock($fp, LOCK_SH);
        $json = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $json === false ? '' : $json;
    }

    public static function updateList(): void
    {
        $plugin_names = self::fetchJson(self::PLUGIN_NAMES_ENDPOINT, 2);
        if (!isset($plugin_names['packageNames']) || !is_array($plugin_names['packageNames'])) {
            throw new RuntimeException(
                'Unexpected plugin list format: ' . var_export($plugin_names, true),
                3
            );
        }

        $psalm_releases = self::fetchPsalmReleases();
        $latest_psalm = $psalm_releases[0];

        $plugins = [];
        $abandoned_count = 0;
        $unreleased_count = 0;
        foreach (array_diff($plugin_names['packageNames'], self::EXCLUDED_PACKAGES) as $package_name) {
            try {
                $package = self::fetchJson("https://packagist.org/packages/$package_name.json", 2)['package'] ?? null;
            } catch (RuntimeException) {
                continue;
            }

            if (!is_array($package) || !isset($package['name'], $package['description'])) {
                continue;
            }

            if (!empty($package['abandoned'])) {
                $abandoned_count++;
                continue;
            }

            $versions_by_stability = self::versionsByStability($package['versions'] ?? []);
            unset($versions_by_stability['dev']);
            if ($versions_by_stability === []) {
                $unreleased_count++;
                continue;
            }

            $stable_versions = $versions_by_stability['stable'] ?? [];
            $bundled = isset($latest_psalm['require'][$package['name']]);

            $plugins[] = new Plugin(
                $package['name'],
                $package['description'],
                (int) ($package['downloads']['monthly'] ?? 0),
                (int) ($package['github_stars'] ?? 0),
                self::lastRelease($stable_versions),
                self::supportedPsalmMajors($stable_versions, $psalm_releases),
                $bundled,
                $bundled ? (self::BUNDLED_PLUGIN_LIBRARIES[$package['name']] ?? []) : [],
            );
        }

        usort(
            $plugins,
            static fn (Plugin $a, Plugin $b): int => $b->monthly_downloads <=> $a->monthly_downloads
        );

        $catalog = new PluginCatalog($latest_psalm['major'], $abandoned_count, $unreleased_count, $plugins);

        $written = file_put_contents(self::PLUGIN_LIST_FILE, json_encode($catalog, JSON_THROW_ON_ERROR), LOCK_EX);

        if (false === $written) {
            throw new RuntimeException(
                'Failed to store plugin list: ' . var_export(error_get_last(), true),
                4
            );
        }
    }

    /**
     * Stable Psalm releases, newest first, with normalized versions.
     *
     * @return non-empty-list<array{version: string, major: int, plugin_api: ?string, require: array<string, string>}>
     */
    private static function fetchPsalmReleases(): array
    {
        $entries = self::fetchJson(self::PSALM_RELEASES_ENDPOINT, 5)['packages']['vimeo/psalm'] ?? null;
        if (!is_array($entries)) {
            throw new RuntimeException('Unexpected vimeo/psalm metadata format', 5);
        }

        $parser = new VersionParser();
        $releases = [];
        foreach (self::expandMinifiedVersions($entries) as $entry) {
            if (!isset($entry['version_normalized']) || VersionParser::parseStability($entry['version']) !== 'stable') {
                continue;
            }

            $require = isset($entry['require']) && is_array($entry['require']) ? $entry['require'] : [];
            $releases[] = [
                'version' => $entry['version_normalized'],
                'major' => (int) $entry['version_normalized'],
                // Psalm pins an exact plugin API version; anything else can't be matched against and counts as none.
                'plugin_api' => self::normalize($parser, $require['psalm/psalm-plugin-api'] ?? null),
                'require' => $require,
            ];
        }

        if ($releases === []) {
            throw new RuntimeException('No stable vimeo/psalm releases found', 5);
        }

        usort($releases, static fn (array $a, array $b): int => version_compare($b['version'], $a['version']));

        return $releases;
    }

    /**
     * Expands Composer v2 minified metadata, where each entry only lists keys that changed from the previous one.
     *
     * @return list<array<string, mixed>>
     */
    private static function expandMinifiedVersions(array $entries): array
    {
        $expanded = [];
        $current = [];
        foreach ($entries as $entry) {
            foreach ((array) $entry as $key => $value) {
                if ($value === '__unset') {
                    unset($current[$key]);
                } else {
                    $current[$key] = $value;
                }
            }
            $expanded[] = $current;
        }

        return $expanded;
    }

    /** @return array<string, list<array<string, mixed>>> versions grouped by stability (stable, RC, beta, alpha, dev) */
    private static function versionsByStability(mixed $versions): array
    {
        $grouped = [];
        foreach (is_array($versions) ? $versions : [] as $version) {
            if (is_array($version) && isset($version['version'])) {
                $grouped[VersionParser::parseStability($version['version'])][] = $version;
            }
        }

        return $grouped;
    }

    private static function lastRelease(array $stable_versions): ?string
    {
        $times = array_filter(array_column($stable_versions, 'time'), 'is_string');

        return $times ? max($times) : null;
    }

    /**
     * Psalm majors that at least one stable release of the plugin can be installed with, judged by its
     * vimeo/psalm and psalm/psalm-plugin-api requirements. Null when no release declares either.
     *
     * @param list<array{version: string, major: int, plugin_api: ?string, require: array<string, string>}> $psalm_releases
     * @return list<int>|null
     */
    private static function supportedPsalmMajors(array $stable_versions, array $psalm_releases): ?array
    {
        $parser = new VersionParser();
        $requirements = [];
        foreach ($stable_versions as $version) {
            $require = is_array($version['require'] ?? null) ? $version['require'] : [];
            $psalm = $require['vimeo/psalm'] ?? null;
            $plugin_api = $require['psalm/psalm-plugin-api'] ?? null;
            $key = json_encode([$psalm, $plugin_api]);
            if (($psalm !== null || $plugin_api !== null) && !isset($requirements[$key])) {
                $requirements[$key] = [
                    self::parseConstraint($parser, $psalm),
                    self::parseConstraint($parser, $plugin_api),
                ];
            }
        }

        if ($requirements === []) {
            return null;
        }

        $majors = [];
        foreach ($psalm_releases as $release) {
            if (isset($majors[$release['major']])) {
                continue;
            }
            foreach ($requirements as [$psalm, $plugin_api]) {
                if (
                    $psalm !== false && $plugin_api !== false
                    && ($psalm === null || $psalm->matches(new Constraint('==', $release['version'])))
                    && ($plugin_api === null
                        || ($release['plugin_api'] !== null
                            && $plugin_api->matches(new Constraint('==', $release['plugin_api']))))
                ) {
                    $majors[$release['major']] = true;
                    break;
                }
            }
        }

        $majors = array_keys($majors);
        sort($majors);

        return $majors;
    }

    /** @return ConstraintInterface|null|false null when absent, false when unparseable */
    private static function parseConstraint(VersionParser $parser, ?string $constraint): ConstraintInterface|null|false
    {
        if ($constraint === null) {
            return null;
        }

        try {
            return $parser->parseConstraints($constraint);
        } catch (UnexpectedValueException) {
            return false;
        }
    }

    private static function normalize(VersionParser $parser, ?string $version): ?string
    {
        if ($version === null) {
            return null;
        }

        try {
            return $parser->normalize($version);
        } catch (UnexpectedValueException) {
            return null;
        }
    }

    private static function fetchJson(string $url, int $error_code): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => self::HTTP_TIMEOUT_SECONDS,
                'user_agent' => self::HTTP_USER_AGENT,
            ],
        ]);
        $json = @file_get_contents($url, false, $context);
        if ($json === false || $json === '') {
            throw new RuntimeException(
                "Failed to fetch $url: " . var_export(error_get_last(), true),
                $error_code
            );
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException("Unexpected response from $url", $error_code);
        }

        return $data;
    }
}
