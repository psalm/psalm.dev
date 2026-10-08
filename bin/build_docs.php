<?php

/**
 * Builds the documentation with the pinned toolchain.
 *
 * mkdocs isn't a composer dependency, so an mkdocs picked up from PATH can be
 * any version: the server had mkdocs 1.1, which double-escapes code spans in
 * headings and cannot render the mkdocs-material 9 theme in theme/. The
 * InstallDocsPythonToolchain migration installs requirements.txt into
 * .venv-docs, and this script builds with that interpreter, never PATH.
 *
 * Deploys run this through composer's post-install-cmd. Checkouts without the
 * virtualenv (a plain "composer install" on a dev machine) are skipped rather
 * than failed; see the README for building the docs locally.
 */

$root = dirname(__DIR__);
$mkdocs = $root . '/.venv-docs/bin/mkdocs';

if (!is_executable($mkdocs)) {
    echo 'No docs toolchain in ' . $root . '/.venv-docs, skipping the docs build.' . PHP_EOL;
    echo 'Run "pip install -r requirements.txt" into that virtualenv to enable it.' . PHP_EOL;
    exit(0);
}

$command = 'cd ' . escapeshellarg($root) . ' && ' . escapeshellarg($mkdocs) . ' build 2>&1';

exec($command, $output, $exit_code);

echo implode(PHP_EOL, $output) . PHP_EOL;

if ($exit_code !== 0) {
    echo 'Docs build failed with status ' . $exit_code . PHP_EOL;
    exit($exit_code);
}

echo 'Docs built with ' . $mkdocs . PHP_EOL;

