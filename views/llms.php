<?php

// /llms.txt and /llms-full.txt (https://llmstxt.org/), built from the docs Markdown (see .htaccess)

use PsalmDotOrg\Docs;

http_response_code(500);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$full = isset($_GET['full']);
// The files the response depends on besides the docs, for its ETag
$code = [__FILE__, dirname(__DIR__) . '/src/Docs.php'];
$all = Docs::all();

$sections = [
    'Running Psalm' => array_values(array_filter(
        Docs::under($all, 'running_psalm/'),
        static fn(string $path): bool => $path !== 'running_psalm/issues.md'
            && !str_starts_with($path, 'running_psalm/issues/'),
    )),
    'Annotating code' => Docs::under($all, 'annotating_code/'),
    'Security analysis (taint analysis)' => Docs::under($all, 'security_analysis/'),
    'Manipulating code' => Docs::under($all, 'manipulating_code/'),
    // under() keeps only files that exist, so a page removed upstream drops out instead of failing
    'Issues' => array_merge(Docs::under($all, 'running_psalm/issues.md'), Docs::under($all, 'running_psalm/issues/')),
    'Contributing' => Docs::under($all, 'contributing/'),
];

$home = Docs::under($all, 'README.md');

$intro = <<<'MD'
# Psalm

> Psalm is a static analysis tool for PHP that finds type-related bugs, checks docblock types and generics, detects security vulnerabilities with taint analysis, and can fix and refactor code automatically.

Every documentation page is also available as Markdown: append `.md` to the page path (https://psalm.dev/docs/running_psalm/installation.md for https://psalm.dev/docs/running_psalm/installation/), or `index.md` to the page URL.
MD;

if ($full) {
    // Everything an agent needs to use Psalm. The issue pages (one per issue type) and the
    // contributor docs stay out to keep the file small; llms.txt links to each of them.
    $paths = array_merge(
        $home,
        $sections['Running Psalm'],
        Docs::under($all, 'running_psalm/issues.md'),
        $sections['Annotating code'],
        $sections['Security analysis (taint analysis)'],
        $sections['Manipulating code'],
    );

    Docs::sendCacheHeaders($paths, $code);

    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');

    echo $intro, "\n\nThis file holds the full text of the docs pages, except the page of each issue type and the contributor guide: see https://psalm.dev/llms.txt for links to those.\n";
    foreach ($paths as $path) {
        echo "\n\n", str_repeat('-', 80), "\nSource: ", Docs::url($path), "\n\n";
        echo rtrim(Docs::contents($path)), "\n";
    }
    exit;
}

Docs::sendCacheHeaders($all, $code);

/**
 * @param list<string> $paths
 * @param bool $notes whether to add the first sentence of each page
 */
$links = static function (array $paths, bool $notes = true): string {
    $lines = [];
    foreach ($paths as $path) {
        $contents = Docs::contents($path);
        $summary = $notes ? Docs::summary($contents) : null;
        $lines[] = '- [' . Docs::title($contents, $path) . '](' . Docs::url($path) . ')'
            . ($summary === null ? '' : ': ' . $summary);
    }

    return implode("\n", $lines);
};

http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');

echo $intro, "\n\n";
echo "## Docs\n\n", $links($home), "\n";
echo "- [Full docs text](https://psalm.dev/llms-full.txt): all of the pages below in one file, except the issue type pages and Contributing\n";

foreach ($sections as $title => $paths) {
    if ($title === 'Contributing') {
        continue;
    }
    // Issue pages are named after the issue Psalm reports; notes would double the file size
    echo "\n## ", $title, "\n\n", $links($paths, $title !== 'Issues'), "\n";
}

echo <<<'MD'

## Playground

- [Psalm playground](https://psalm.dev/): run Psalm on a PHP snippet in the browser. Saved snippets live at `https://psalm.dev/r/<id>`; `https://psalm.dev/r/<id>.json` returns the code and settings as JSON, `https://psalm.dev/r/<id>/raw` the code as plain text, and `https://psalm.dev/r/<id>/results` the analysis results as JSON

## Optional


MD;
echo $links($sections['Contributing']), "\n";
echo <<<'MD'
- [Plugins](https://psalm.dev/plugins): Psalm plugins published on Packagist
- [Articles](https://psalm.dev/articles): articles about Psalm features and releases
- [Configuration schema](https://psalm.dev/schema/config): XSD schema of psalm.xml
- [Source code](https://github.com/vimeo/psalm): Psalm on GitHub

MD;
