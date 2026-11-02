<?php

/**
 * Resolves the installed vimeo/psalm commit to the nearest reachable tag and the number of
 * commits on top of it, and stores both for OnlineChecker.
 *
 * vimeo/psalm is installed from a dist zip, so the tag history is read from a treeless clone.
 */

use Composer\InstalledVersions;
use PsalmDotOrg\OnlineChecker;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @param list<string> $args
 */
function git(array $args): ?string
{
    exec('git ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>/dev/null', $output, $exit_code);

    return $exit_code === 0 ? trim(implode("\n", $output)) : null;
}

function removeDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($dir);
}

$commit = InstalledVersions::getReference('vimeo/psalm');
$pretty_version = (string) InstalledVersions::getPrettyVersion('vimeo/psalm');

if ($commit === null) {
    echo 'Cannot determine the installed vimeo/psalm commit' . PHP_EOL;
    exit(0);
}

// dev-master -> master, 6.x-dev -> 6.x
if (strpos($pretty_version, 'dev-') === 0) {
    $branch = substr($pretty_version, 4);
} elseif (substr($pretty_version, -4) === '-dev') {
    $branch = substr($pretty_version, 0, -4);
} else {
    $branch = null;
}

$clone_dir = sys_get_temp_dir() . '/psalm-version-' . bin2hex(random_bytes(8));
$tag = null;
$commits_ahead = null;

try {
    $clone_args = ['clone', '--quiet', '--bare', '--filter=tree:0'];

    if ($branch !== null) {
        $clone_args = array_merge($clone_args, ['--single-branch', '--branch', $branch]);
    }

    if (git(array_merge($clone_args, ['https://github.com/vimeo/psalm.git', $clone_dir])) !== null) {
        // e.g. "7.0.0-rc28-12-gb4ab875"; the tag itself may contain dashes, so match from the end
        $description = git(['-C', $clone_dir, 'describe', '--tags', '--long', $commit]);

        if ($description !== null && preg_match('/^(.+)-(\d+)-g[0-9a-f]+$/', $description, $matches) === 1) {
            $tag = $matches[1];
            $commits_ahead = (int) $matches[2];
        }
    }
} finally {
    removeDir($clone_dir);
}

if ($tag === null || $commits_ahead === null) {
    @unlink(OnlineChecker::PSALM_VERSION_FILE);
    echo 'Cannot determine the vimeo/psalm tag for commit ' . $commit . PHP_EOL;
    exit(0);
}

file_put_contents(
    OnlineChecker::PSALM_VERSION_FILE,
    json_encode(['commit' => $commit, 'tag' => $tag, 'commits_ahead' => $commits_ahead], JSON_PRETTY_PRINT) . PHP_EOL
);

echo 'vimeo/psalm version: ' . $tag . ($commits_ahead > 0 ? '+' . $commits_ahead : '') . ' (' . substr($commit, 0, 7) . ')' . PHP_EOL;
