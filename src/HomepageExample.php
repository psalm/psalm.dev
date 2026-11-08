<?php declare(strict_types=1);

namespace PsalmDotOrg;

use Composer\InstalledVersions;

use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function getmypid;
use function hash;
use function is_array;
use function is_dir;
use function json_decode;
use function json_encode;
use function ksort;
use function mkdir;
use function rename;
use function sys_get_temp_dir;

/**
 * The code shown in the homepage playground, and a file cache of its /check results: every homepage view
 * analyzes it, and its result only changes with the settings, PHP version, level, Psalm or this site's checker.
 */
final class HomepageExample
{
    public const string CODE = <<<'PHP'
<?php

/**
 * @return array<string>
 */
function takesAnInt(int $i) {
    return [$i, "hello"];
}

$data = ["some text", 5];
takesAnInt($data[0]);

$condition = rand(0, 5);
if ($condition) {
} elseif ($condition) {}
PHP;

    /**
     * OnlineChecker::getResults() for CODE, cached per settings, PHP version, level, Psalm version and checker version.
     */
    public static function results(array $settings, bool $fix_file, string $php_version, int $error_level): array
    {
        ksort($settings);

        $key = hash('sha256', json_encode([
            $settings,
            $fix_file,
            $php_version,
            $error_level,
            InstalledVersions::getPrettyVersion('vimeo/psalm'),
            InstalledVersions::getReference('vimeo/psalm'),
            // a deploy that changes the response shape must not serve old results
            filemtime(__DIR__ . '/OnlineChecker.php'),
        ], JSON_THROW_ON_ERROR));

        $dir = sys_get_temp_dir() . '/psalm-dev-homepage';
        $file = $dir . '/' . $key . '.json';
        $cached = @file_get_contents($file);

        if ($cached !== false) {
            $results = json_decode($cached, true);

            if (is_array($results)) {
                return $results;
            }
        }

        $results = OnlineChecker::getResults(self::CODE, $settings, $fix_file, $php_version, $error_level);

        if (is_dir($dir) || @mkdir($dir, 0o775, true) || is_dir($dir)) {
            // write then rename, so a concurrent request never reads a half-written file
            $tmp = $file . '.' . (int) getmypid();

            if (@file_put_contents($tmp, json_encode($results, JSON_THROW_ON_ERROR)) !== false) {
                @rename($tmp, $file);
            }
        }

        return $results;
    }
}
