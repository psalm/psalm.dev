<?php

/**
 * Warms psalm_version.json for the installed vimeo/psalm commit on composer install/update.
 * OnlineChecker also resolves it lazily, since Psalm can be updated without this script running.
 */

use Composer\InstalledVersions;
use PsalmDotOrg\PsalmVersion;

require_once __DIR__ . '/../vendor/autoload.php';

$commit = InstalledVersions::getReference('vimeo/psalm');

if ($commit === null) {
    echo 'Cannot determine the installed vimeo/psalm commit' . PHP_EOL;
    exit(0);
}

$resolved = PsalmVersion::resolve($commit, 15.0);

if ($resolved === null) {
    @unlink(PsalmVersion::CACHE_FILE);
    echo 'Cannot determine the vimeo/psalm tag for commit ' . $commit . PHP_EOL;
    exit(0);
}

[$tag, $commits_ahead] = $resolved;

file_put_contents(
    PsalmVersion::CACHE_FILE,
    json_encode(['commit' => $commit, 'tag' => $tag, 'commits_ahead' => $commits_ahead], JSON_PRETTY_PRINT) . PHP_EOL
);

echo 'vimeo/psalm version: ' . $tag . ($commits_ahead > 0 ? '+' . $commits_ahead : '') . ' (' . substr($commit, 0, 7) . ')' . PHP_EOL;
