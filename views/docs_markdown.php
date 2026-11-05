<?php

// The Markdown source of a docs page: /docs/<path>.md (see .htaccess)

use PsalmDotOrg\Docs;

http_response_code(500);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$requested = $_GET['path'] ?? '';
$path = is_string($requested) ? Docs::locate($requested) : null;

if ($path === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex');
    echo "Not found. The list of docs pages is at https://psalm.dev/llms.txt\n";
    exit;
}

if ($path !== $requested) {
    // A page URL with index.md appended: send it to the file, so relative links in it work
    http_response_code(301);
    header('Location: ' . Docs::url($path));
    header('Cache-Control: public, max-age=3600');
    exit;
}

Docs::sendCacheHeaders([$path], [__FILE__, dirname(__DIR__) . '/src/Docs.php']);

http_response_code(200);
header('Content-Type: text/markdown; charset=utf-8');
header('Link: <' . Docs::pageUrl($path) . '>; rel="canonical"');
echo Docs::contents($path);
